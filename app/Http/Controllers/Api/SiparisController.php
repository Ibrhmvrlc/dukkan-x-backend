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
        $musteri = Musteriler::findOrFail($musteriId);
        $urunler = Urunler::all();

        return response()->json([
            'musteri' => $musteri,
            'urunler' => $urunler
        ]);
    }

    // POST /api/siparisler
    public function store(Request $request)
    {
        $validated = $request->validate([
            'musteri_id' => 'required|exists:musteriler,id',
            'urunler' => 'required|array|min:1',
            'urunler.*.urun_id' => 'required|exists:urunler,id',
            'urunler.*.miktar' => 'required|numeric|min:1',
            'urunler.*.fiyat' => 'required|numeric|min:0',
            'yetkili' => 'nullable|string|max:255',
            'iskonto' => 'nullable|numeric|min:0',
            'kdv' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            $siparis = Siparis::create([
                'musteri_id' => $validated['musteri_id'],
                'yetkili' => $validated['yetkili'] ?? null,
                'kdv' => $validated['kdv'] ?? 0,
                'iskonto' => $validated['iskonto'] ?? 0,
            ]);

            foreach ($validated['urunler'] as $item) {
                $siparis->urunler()->attach($item['urun_id'], [
                    'miktar' => $item['miktar'],
                    'fiyat' => $item['fiyat'],
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Sipariş başarıyla oluşturuldu.',
                'siparis_id' => $siparis->id,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Sipariş oluşturulurken bir hata oluştu.',
                'error' => $e->getMessage()
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
