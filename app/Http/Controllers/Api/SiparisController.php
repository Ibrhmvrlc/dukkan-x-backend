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
            ->paginate($perPage)
            ->through(function ($s) {
                return [
                    'id'            => $s->id,
                    'tarih'         => $s->tarih,
                    'fatura_no'     => $s->fatura_no,
                    'musteri'       => $s->musteri,
                    'ara_toplam'    => (float) $s->ara_toplam,    // 🔵
                    'kdv_toplam'    => (float) $s->kdv_toplam,    // 🔵
                    'toplam_tutar'  => (float) $s->toplam_tutar,  // 🔵
                ];
            });

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
                'fatura_no'       => $s->fatura_no,
                'not'             => $s->not ? $s->not : '',
                'durum'           => $s->fatura_no ? 'Faturalandı' : 'Beklemede',
                'ara_toplam'      => (float) $s->ara_toplam,     // 🔵 eklendi
                'kdv_toplam'      => (float) $s->kdv_toplam,     // 🔵 eklendi
                'toplam_tutar'    => (float) $s->toplam_tutar,   // 🔵 eklendi
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
            'urunler.*.kdv'         => 'nullable|numeric|min:0',
            'not'                   => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated) {
            $musteri = Musteriler::findOrFail($validated['musteri_id']);
            $musteriIskonto = (float) ($musteri->iskonto_orani ?? 0);

            $siparis = Siparis::create([
                'musteri_id'         => $validated['musteri_id'],
                'teslimat_adresi_id' => $validated['teslimat_adresi_id'],
                'yetkili_id'         => $validated['yetkili_id'],
                'not'                => $validated['not'] ?? null,
                'tarih'              => now(),
            ]);

            $urunIds = collect($validated['urunler'])->pluck('urun_id')->all();
            $kdvMap  = Urunler::whereIn('id', $urunIds)->pluck('kdv_orani', 'id');

            $attachData = [];
            foreach ($validated['urunler'] as $item) {
                $urunId   = $item['urun_id'];
                $urunKdv  = $kdvMap[$urunId] ?? 10;
                $pivotKdv = $item['kdv'] ?? $urunKdv;

                $attachData[$urunId] = [
                    'adet'          => $item['miktar'],
                    'birim_fiyat'   => $item['fiyat'],
                    'iskonto_orani' => $musteriIskonto,
                    'kdv_orani'     => $pivotKdv,
                ];
            }

            $siparis->urunler()->attach($attachData);

            // 🔴 Kritik satır: pivot değişti → toplamları hesapla ve kaydet
            $siparis->recalcTotals()->save();

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

        $durum = $siparis->fatura_no ? 'Faturalandı' : 'Beklemede';

        $data = [
            'id'               => $siparis->id,
            'tarih'            => $siparis->tarih,
            'fatura_no'        => $siparis->fatura_no,
            'not'              => $siparis->not,
            'durum'            => $durum,
            'ara_toplam'       => (float) $siparis->ara_toplam,     // 🔵
            'kdv_toplam'       => (float) $siparis->kdv_toplam,     // 🔵
            'toplam_tutar'     => (float) $siparis->toplam_tutar,   // 🔵
            'musteri'          => $siparis->musteri,
            'yetkili'          => $siparis->yetkili,
            'teslimat_adresi'  => $siparis->teslimatAdresi,
            'urunler'          => $siparis->urunler->map(function ($u) {
                return [
                    'urun'          => $u,
                    'adet'          => $u->pivot->adet,
                    'birim_fiyat'   => $u->pivot->birim_fiyat,
                    'iskonto_orani' => $u->pivot->iskonto_orani,
                    'kdv_orani'     => $u->pivot->kdv_orani,
                ];
            })->values(),
        ];

        return response()->json(['data' => $data]);
    }


    // PUT /api/siparisler/{id}
    public function update(Request $request, $id)
    {
        /** @var \App\Models\Siparis $siparis */
        $siparis = Siparis::findOrFail($id);

        $validated = $request->validate([
            'tarih' => ['nullable','date'],
            'fatura_no' => ['nullable','string','max:255'],
            'durum' => ['nullable','string','max:50'],
            'yetkili_id' => ['nullable','exists:yetkililer,id'],
            'teslimat_adresi_id' => ['nullable','exists:teslimat_adresleri,id'],
            'not' => ['nullable', 'string', 'max:2000'],

            'urunler' => ['array'],
            'urunler.*.urun_id' => ['required','exists:urunler,id'],
            'urunler.*.adet' => ['required','numeric','min:0'],
            'urunler.*.birim_fiyat' => ['required','numeric','min:0'],
            'urunler.*.iskonto_orani' => ['nullable','numeric','min:0'],
            'urunler.*.kdv_orani' => ['nullable','numeric','min:0'],
        ]);

        return DB::transaction(function () use ($request, $siparis, $validated) {
            // Başlık alanları
            $siparis->fill($request->only([
                'tarih','fatura_no','durum','yetkili_id','teslimat_adresi_id','not'
            ]));
            $siparis->save();

            // Pivot eşleme (gönderildiyse)
            if (array_key_exists('urunler', $validated)) {
                $map = collect($validated['urunler'])->mapWithKeys(function ($u) {
                    return [
                        $u['urun_id'] => [
                            'adet'          => $u['adet'],
                            'birim_fiyat'   => $u['birim_fiyat'],
                            'iskonto_orani' => $u['iskonto_orani'] ?? 0,
                            'kdv_orani'     => $u['kdv_orani'] ?? 0,
                        ]
                    ];
                })->toArray();

                $siparis->urunler()->sync($map);

                // 🔴 Kritik satır: pivot değişti → toplamları hesapla ve kaydet
                $siparis->recalcTotals()->save();
            }

            $siparis->load(['urunler' => function($q) {
                $q->withPivot(['adet','birim_fiyat','iskonto_orani','kdv_orani']);
            }, 'yetkili', 'teslimatAdresi']);

            return response()->json($siparis, 200);
        });
    }



    // DELETE /api/siparisler/{id}
    public function destroy($id)
    {
        $siparis = Siparis::findOrFail($id);
        $siparis->delete();
        return response()->json(['message' => 'Sipariş silindi.']);
    }
}