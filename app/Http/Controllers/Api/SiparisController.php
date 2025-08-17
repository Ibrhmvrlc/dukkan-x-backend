<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Siparis;
use App\Models\Musteriler;
use App\Models\Urunler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SiparisController extends Controller
{
    // App\Http\Controllers\Api\SiparisController.php

    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 25);

        $paginator = Siparis::with('musteri')
            ->latest()
            ->paginate($perPage);

        // Standart Laravel pagination döner (current_page, data, from, last_page, per_page, to, total ...)
        return response()->json($paginator);
    }

    /**
     * GET /api/musteriler/{musteriId}/siparisler
     * Server-side pagination + (opsiyonel) basit arama.
     */
    public function siparislerByMusteri(Request $request, $musteri)
    {
        $perPage = (int) $request->input('per_page', 25);
        $q       = trim((string) $request->input('q', ''));

        $query = Siparis::with(['urunler','yetkili','teslimatAdresi'])
            ->where('musteri_id', $musteri)
            ->latest();

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->whereDate('tarih', $q)
                    ->orWhereHas('yetkili', fn($yy) => $yy->where('isim', 'like', "%{$q}%"))
                    ->orWhereHas('teslimatAdresi', fn($ta) => $ta->where('adres', 'like', "%{$q}%"))
                    ->orWhereHas('urunler', fn($uu) => $uu->where('isim', 'like', "%{$q}%"));
            });
        }

        $paginator = $query->paginate($perPage)->through(function ($s) {
            return [
                'id'              => $s->id,
                'tarih'           => $s->tarih,
                'fatura_no'       => $s->fatura_no,                      // ← EKLENDİ
                'durum'           => $s->fatura_no ? 'Faturalandı' : 'Beklemede', // ← İsteğe bağlı
                'yetkili'         => $s->yetkili,
                'teslimat_adresi' => $s->teslimatAdresi,
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
        });


        return response()->json($paginator);
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
            // Elle iskonto alanlarını ŞİMDİLİK kaldırdık:
            // 'urunler.*.iskonto'   => 'nullable|numeric|min:0',
            // 'iskonto'             => 'nullable|numeric|min:0',
            'urunler.*.kdv'         => 'nullable|numeric|min:0',
            'not'                   => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated) {
            // 0) Müşterinin genel iskonto oranını al
            /** @var \App\Models\Musteriler $musteri */
            $musteri = Musteriler::findOrFail($validated['musteri_id']);
            $musteriIskonto = (float) ($musteri->iskonto_orani ?? 0); // null ise 0

            // 1) Sipariş başlığı
            $siparis = Siparis::create([
                'musteri_id'         => $validated['musteri_id'],
                'teslimat_adresi_id' => $validated['teslimat_adresi_id'],
                'yetkili_id'         => $validated['yetkili_id'],
                'not'                => $validated['not'] ?? null,
                'tarih'              => now(), // DATE sütunu
            ]);

            // 2) Ürün KDV’lerini haritalandır
            $urunIds = collect($validated['urunler'])->pluck('urun_id')->all();
            // Ürün tablosundaki alan adın nasılsa ona göre değiştir (kdv_orani / kdv)
            $kdvMap = Urunler::whereIn('id', $urunIds)->pluck('kdv_orani', 'id');

            // 3) Pivot verisi: iskonto_orani = MUSTERILER.iskonto_orani
            $attachData = [];
            foreach ($validated['urunler'] as $item) {
                $urunId   = $item['urun_id'];
                $urunKdv  = $kdvMap[$urunId] ?? 10;        // ürün bulunamazsa 10 varsay
                $pivotKdv = $item['kdv'] ?? $urunKdv;      // satırda gönderildiyse onu al

                $attachData[$urunId] = [
                    'adet'          => $item['miktar'],
                    'birim_fiyat'   => $item['fiyat'],
                    'iskonto_orani' => $musteriIskonto,    // ← tek kaynağımız müşteri tablosu
                    'kdv_orani'     => $pivotKdv,
                ];
            }

            $siparis->urunler()->attach($attachData);

            return response()->json([
                'message' => 'Sipariş oluşturuldu.',
                'data'    => $siparis->id
            ], 201);
        });
    }


    public function show($id)
    {
        $siparis = Siparis::with(['musteri', 'yetkili', 'teslimatAdresi', 'urunler'])
            ->findOrFail($id);

        // basit durum türetimi (isteğe göre genişletilebilir)
        $durum = $siparis->fatura_no ? 'Faturalandı' : 'Beklemede';

        $data = [
            'id'               => $siparis->id,
            'tarih'            => $siparis->tarih,
            'fatura_no'        => $siparis->fatura_no,   // ← eklendi
            'durum'            => $durum,                // ← opsiyonel
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
}