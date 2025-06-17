<?php

namespace App\Http\Controllers\Api;

use App\Exports\UrunlerExport;
use App\Http\Controllers\Controller;
use App\Http\Resources\UrunResource;
use App\Models\Siparis;
use App\Models\Urunler;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

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

    public function update(Request $request, $id)
    {
        $urun = Urunler::findOrFail($id);

        $validated = $request->validate([
            'kod' => 'required|string|max:255',
            'isim' => 'nullable|string|max:255',
            'cesit' => 'nullable|string|max:255',
            'birim' => 'nullable|string|max:255',
            'tedarik_fiyati' => 'nullable|numeric',
            'satis_fiyati' => 'nullable|numeric',
            'stok_miktari' => 'nullable|numeric',
            'kritik_stok' => 'nullable|numeric',
            'kdv_orani' => 'nullable|numeric',
            'aktif' => 'boolean',
        ]);

        $urun->update($validated);

        return response()->json(['message' => 'Ürün başarıyla güncellendi.']);
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

    public function bulkUpload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv,txt'
        ]);

        try {
            $path = $request->file('file')->getRealPath();
            $data = Excel::toArray([], $request->file('file'));

            $rows = $data[0]; // ilk sayfa
            unset($rows[0]); // başlığı kaldır

            foreach ($rows as $row) {
                // Dizi sırasına göre ekleyebilirsiniz veya başlıkları da alabilirsiniz
                Urunler::create([
                    'kod' => $row[0],
                    'isim' => $row[1],
                    'cesit' => $row[2],
                    'birim' => $row[3],
                    'tedarik_fiyati' => floatval($row[4]),
                    'satis_fiyati' => floatval($row[5]),
                    'stok_miktari' => intval($row[6]),
                    'kritik_stok' => intval($row[7]),
                    'aktif' => filter_var($row[8], FILTER_VALIDATE_BOOLEAN),
                ]);
            }

            return response()->json(['message' => 'Ürünler başarıyla eklendi.']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Yükleme sırasında hata: ' . $e->getMessage()], 500);
        }
    }

     public function export()
    {
        return Excel::download(new UrunlerExport, 'urunler.xlsx');
    }
}