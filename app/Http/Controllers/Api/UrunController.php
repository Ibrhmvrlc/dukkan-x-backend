<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UrunResource;
use App\Models\Urun;
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
            'aciklama' => 'nullable|string',
            'birim' => 'required|string|max:50',
            'fiyat' => 'required|numeric|min:0',
            'kdv_orani' => 'required|integer|in:1,8,18',
            'stok_miktari' => 'nullable|numeric|min:0',
            'kritik_stok' => 'nullable|numeric|min:0',
            'aktif' => 'boolean',
        ]);

        $urun = Urunler::create($data);

        return new UrunResource($urun);
    }

    public function show(Urunler $urun)
    {
        return new UrunResource($urun);
    }

    public function update(Request $request, Urunler $urun)
    {
        $data = $request->validate([
            'kod' => 'nullable|string|max:255',
            'isim' => 'required|string|max:255',
            'aciklama' => 'nullable|string',
            'birim' => 'required|string|max:50',
            'fiyat' => 'required|numeric|min:0',
            'kdv_orani' => 'required|integer|in:1,8,18',
            'stok_miktari' => 'nullable|numeric|min:0',
            'kritik_stok' => 'nullable|numeric|min:0',
            'aktif' => 'boolean',
        ]);

        $urun->update($data);

        return new UrunResource($urun);
    }

    public function destroy(Urunler $urun)
    {
        $urun->delete();

        return response()->json(['message' => 'Ürün başarıyla silindi.']);
    }
}