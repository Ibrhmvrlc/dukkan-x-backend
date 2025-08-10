<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Siparis;
use App\Models\Musteri;
use App\Models\Musteriler;
use App\Models\Urun;
use App\Models\Urunler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SiparisController extends Controller
{
    // GET /api/siparisler
    public function index()
    {
        $siparisler = Siparis::with('musteri')->latest()->get();
        return response()->json(['data' => $siparisler]);
    }

    // GET /api/siparisler/create/{musteri}
    public function createWithMusteri($musteriId)
    {
        $musteri = Musteriler::with('teslimat_adresleri')->with('yetkililer')->findOrFail($musteriId); // ilişkili adresleri de al
        $urunler = Urunler::all();

        return response()->json([
            'musteri' => $musteri,
            'urunler' => $urunler,
            'teslimat_adresleri' => $musteri->teslimat_adresleri, // ayrı key olarak da döndürebiliriz
            'yetkililer' => $musteri->yetkililer // ayrı key olarak da döndürebiliriz
        ]);
    }

    // POST /api/siparisler
   public function store(Request $request)
    {
        $validated = $request->validate([
            'musteri_id'            => 'required|exists:musteriler,id',
            'teslimat_adresi_id'    => 'required|exists:teslimat_adresleri,id',
            'yetkili_id'            => 'required|exists:yetkililer,id',
            'urunler'               => 'required|array|min:1',
            'urunler.*.urun_id'     => 'required|exists:urunler,id',
            'urunler.*.miktar'      => 'required|numeric|min:1',
            'urunler.*.fiyat'       => 'required|numeric|min:0',
            // opsiyonel override:
            'urunler.*.kdv'         => 'nullable|numeric|min:0',
            'urunler.*.iskonto'     => 'nullable|numeric|min:0',
            'iskonto'               => 'nullable|numeric|min:0',
            'not'                   => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated) {
            // 1) Sipariş başlığı
            $siparis = Siparis::create([
                'musteri_id'         => $validated['musteri_id'],
                'teslimat_adresi_id' => $validated['teslimat_adresi_id'],
                'yetkili_id'         => $validated['yetkili_id'],
                'not'                => $validated['not'] ?? null,
                'tarih'              => now(), // DATE sütunu
            ]);

            // 2) Ürünlerden KDV oranlarını topla
            $urunIds = collect($validated['urunler'])->pluck('urun_id')->all();

            // ÜRÜN TABLOSU: KDV alan adın nasılsa ona göre değiştir (ör. 'kdv_orani' ya da 'kdv')
            $kdvMap = Urunler::whereIn('id', $urunIds)->pluck('kdv_orani', 'id'); 
            // eğer sütun adın 'kdv' ise: ->pluck('kdv','id')

            $defaultIskonto = $validated['iskonto'] ?? 0;

            // 3) Pivot’a ekle (adet, birim, iskonto, kdv)
            $attachData = [];
            foreach ($validated['urunler'] as $item) {
                $urunId = $item['urun_id'];
                $urunKdv = $kdvMap[$urunId] ?? 10;                 // ürün yoksa 10 kabul (güvenlik ağı)
                $pivotKdv = $item['kdv'] ?? $urunKdv;               // istenirse override

                $attachData[$urunId] = [
                    'adet'          => $item['miktar'],
                    'birim_fiyat'   => $item['fiyat'],
                    'iskonto_orani' => $item['iskonto'] ?? $defaultIskonto,
                    'kdv_orani'     => $pivotKdv,                    // PİVOTTA TUTULUYOR
                ];
            }

            $siparis->urunler()->attach($attachData);

            return response()->json(['message' => 'Sipariş oluşturuldu.', 'data' => $siparis->id], 201);
        });
    }


    // GET /api/siparisler/{id}
   public function show($id)
    {
        $siparis = Siparis::with(['musteri', 'yetkili', 'teslimatAdresi', 'urunler'])
            ->findOrFail($id);

        // Aynı transform’u istersen burada da uygulayabilirsin
        $data = [
            'id'               => $siparis->id,
            'tarih'            => $siparis->tarih,
            'musteri'          => $siparis->musteri,
            'yetkili'          => $siparis->yetkili,
            'teslimat_adresi'  => $siparis->teslimatAdresi,
            'urunler'          => $siparis->urunler->map(function ($u) {
                return [
                    'urun'        => $u,
                    'adet'        => $u->pivot->adet,
                    'birim_fiyat' => $u->pivot->birim_fiyat,
                ];
            })->values(),
        ];

        return response()->json(['data' => $data]);
    }


    // PUT /api/siparisler/{id}
    public function update(Request $request, $id)
    {
        $siparis = Siparis::findOrFail($id);
        $siparis->update($request->only('yetkili', 'kdv', 'iskonto'));
        return response()->json(['message' => 'Sipariş güncellendi.']);
    }

    // DELETE /api/siparisler/{id}
    public function destroy($id)
    {
        $siparis = Siparis::findOrFail($id);
        $siparis->delete();
        return response()->json(['message' => 'Sipariş silindi.']);
    }

    public function siparislerByMusteri($musteriId)
    {
        $siparisler = Siparis::with(['urunler','yetkili','teslimatAdresi'])
            ->where('musteri_id', $musteriId)
            ->latest()
            ->get();

        $response = $siparisler->map(function ($s) {
            return [
                'id'              => $s->id,
                'tarih'           => $s->tarih,
                'yetkili'         => $s->yetkili,          // {id, isim...}
                'teslimat_adresi' => $s->teslimatAdresi,   // {id, adres...}
                'urunler'         => $s->urunler->map(function ($u) {
                    return [
                        'urun'          => $u,
                        'adet'          => $u->pivot->adet,
                        'birim_fiyat'   => $u->pivot->birim_fiyat,
                        'kdv_orani'     => $u->pivot->kdv_orani,
                        'iskonto_orani' => $u->pivot->iskonto_orani,
                    ];
                })->values(),
            ];
        })->values();

        return response()->json(['data' => $response]);
    }

}
