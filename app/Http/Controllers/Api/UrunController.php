<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UrunResource;
use App\Models\Siparis;
use App\Models\Urunler;
use Illuminate\Http\Request;

class UrunController extends Controller
{
    public function index()
    {
        $urunler = Urunler::orderBy('created_at', 'desc')->get();
        return UrunResource::collection($urunler);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'kod' => 'nullable|string|max:255',
            'isim' => 'required|string|max:255',
            'cesit' => 'nullable|string',
            'birim' => 'required|string|max:50',
            'satis_fiyati' => 'required|numeric|min:0',
            'tedarik_fiyati' => 'required|numeric|min:0',
            'kdv_orani' => 'required|integer',
            'stok_miktari' => 'nullable|numeric|min:0',
            'kritik_stok' => 'nullable|numeric|min:0',
            'aktif' => 'boolean',
        ]);

        $urun = Urunler::create($data);

        return new UrunResource($urun);
    }

    public function show($id)
    {
        $urun = Urunler::findOrFail($id); // not found durumunda 404 atar
        return new UrunResource($urun);
    }

    public function update(Request $request, Urunler $urun)
    {
        $data = $request->validate([
            'kod' => 'nullable|string|max:255',
            'isim' => 'required|string|max:255',
            'cesit' => 'nullable|string',
            'birim' => 'required|string|max:50',
            'satis_fiyati' => 'required|numeric|min:0',
            'tedarik_fiyati' => 'required|numeric|min:0',
            'kdv_orani' => 'required|integer',
            'stok_miktari' => 'nullable|numeric|min:0',
            'kritik_stok' => 'nullable|numeric|min:0',
            'aktif' => 'boolean',
        ]);

        $urun->update($data);

        return new UrunResource($urun);
    }

    public function destroy(Urunler $urun){
        $urun->delete();

        return response()->json(['message' => 'Ürün başarıyla silindi.']);
    }

    public function grafik($urunId)
    {
        $siparisler = Siparis::where('urun_id', $urunId)->get();

        $aylikToplamlar = [];

        foreach ($siparisler as $siparis) {
            $ay = (int) date('n', strtotime($siparis->created_at));
            $adet = $siparis->adet ?? 0;

            $aylikToplamlar[$ay] = ($aylikToplamlar[$ay] ?? 0) + $adet;
        }

        // Tüm aylar için sıfırla
        $veri = [];
        foreach (range(1, 12) as $ay) {
            $veri[] = $aylikToplamlar[$ay] ?? 0;
        }

        return response()->json(['data' => $veri]);
    }

}