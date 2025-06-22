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
            'musteri_id' => 'required|exists:musteriler,id',
            'teslimat_adresi_id' => 'required|exists:teslimat_adresleri,id',
            'yetkili_id' => 'required|exists:yetkililer,id',
            'urunler' => 'required|array|min:1',
            'urunler.*.urun_id' => 'required|exists:urunler,id',
            'urunler.*.miktar' => 'required|numeric|min:1',
            'urunler.*.fiyat' => 'required|numeric|min:0',
            'yetkili' => 'nullable|string|max:255',
            'not' => 'nullable|string',
            'iskonto' => 'nullable|numeric|min:0',
            'kdv' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            foreach ($validated['urunler'] as $item) {
                Siparis::create([
                    'musteri_id' => $validated['musteri_id'],
                    'urun_id' => $item['urun_id'],
                    'teslimat_adresi_id' => $validated['teslimat_adresi_id'],
                    'yetkili_id' => $validated['yetkili_id'],
                    'adet' => $item['miktar'],
                    'birim_fiyat' => $item['fiyat'],
                    'kdv_orani' => $validated['kdv'] ?? 10,
                    'iskonto_orani' => $validated['iskonto'] ?? 0,
                    'not' => $validated['not'] ?? null,
                    'tarih' => now(),
                    // 'yetkili_id' => bu alan ayrı şekilde implement edilecekse ilişkili ID gönderilmeli
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Sipariş(ler) başarıyla oluşturuldu.',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Sipariş oluşturulurken bir hata oluştu.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // GET /api/siparisler/{id}
    public function show($id)
    {
        $siparis = Siparis::with(['musteri', 'urunler'])->findOrFail($id);
        return response()->json(['data' => $siparis]);
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
