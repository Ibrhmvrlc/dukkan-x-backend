<?php

namespace App\Http\Controllers\Api;

use App\Exports\UrunlerExport;
use App\Http\Controllers\Controller;
use App\Http\Resources\UrunResource;
use App\Models\Siparis;
use App\Models\Tedarikci;
use App\Models\Urunler;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;

class UrunController extends Controller
{
    public function index()
    {
        $urunler = Urunler::with('tedarikci')->orderBy('created_at', 'desc')->get();
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
            'marka' => 'required|string|max:255',
            'tedarikci_id' => 'required|exists:tedarikciler,id',
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
            'marka' => 'nullable|string|max:255',
            'tedarikci_id' => 'nullable|exists:tedarikciler,id',
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
            $data = Excel::toArray([], $request->file('file'));
            $rows = $data[0];

            if (empty($rows) || count($rows) < 2) {
                return response()->json(['error' => 'Yetersiz veri: En az başlık + 1 veri satırı olmalı.'], 400);
            }

            // Başlıkları normalize et
            function normalizeHeader($header) {
                $header = preg_replace('/\(.+\)/', '', $header); // "(₺)", "(%)" vs. parantez içini sil
                $header = trim($header);                         // boşlukları sil
                return Str::slug($header, '_');                  // slug'a çevir
            }

            // Tedarikçi adlarını normalize et
            function normalizeCompanyName($name) {
                $name = strtolower($name);
                $name = str_replace(
                    ['ltd.', 'ltd', 'şti.', 'şti', 'san.', 'san', 'tic.', 'tic', 'a.ş.', 'aş',
                    'anonim', 'limited', 'şirketi', '.', ',', '̇'],
                    '',
                    $name
                );
                $name = preg_replace('/[^\x20-\x7E]/u', '', $name); // Türkçe gibi ASCII dışı karakterleri temizle
                $name = preg_replace('/\s+/', ' ', $name); // fazla boşlukları tek boşluğa indir
                return trim($name);
            }

            $headers = array_map(fn($h) => normalizeHeader($h), $rows[0]);
            unset($rows[0]);

            // Beklenen başlıklar (normalize edilmiş haliyle)
            $requiredHeaders = [
                'kod', 'isim', 'cesit', 'marka', 'birim',
                'satis_fiyati', 'kdv_orani', 'stok_miktari',
                'kritik_stok', 'tedarik_fiyati', 'aktif', 'tedarikci'
            ];

            // Eksik başlık kontrolü
            foreach ($requiredHeaders as $header) {
                if (!in_array($header, $headers)) {
                    return response()->json([
                        'error' => "Eksik veya uyumsuz başlık: $header",
                        'bulunan' => $headers,
                        'beklenen' => $requiredHeaders,
                    ], 400);
                }
            }

            $indexMap = array_flip($headers);
            $tedarikciler = Tedarikci::all(['id', 'unvan']);

            foreach ($rows as $row) {
                if (empty($row[$indexMap['kod']]) && empty($row[$indexMap['isim']])) {
                    continue; // boş satır
                }

                $tedarikciAdi = trim($row[$indexMap['tedarikci']] ?? '');
                $normalizedInput = normalizeCompanyName($tedarikciAdi);

                $eslesenTedarikciId = null;
                $maxOran = 0;

                foreach ($tedarikciler as $tedarikci) {
                    $normalizedDbName = normalizeCompanyName($tedarikci->unvan);

                    // Tam eşleşme varsa direkt al
                    if ($normalizedInput === $normalizedDbName) {
                        $eslesenTedarikciId = $tedarikci->id;
                        break;
                    }

                    // Benzerlik oranı ile eşleştir
                    similar_text($normalizedInput, $normalizedDbName, $oran);
                    if ($oran > $maxOran) {
                        $maxOran = $oran;
                        $eslesenTedarikciId = $tedarikci->id;
                    }
                }

                // %60 ve üzeri benzerse eşleştir, değilse varsayılan 1
                $finalTedarikciId = $maxOran >= 60 ? $eslesenTedarikciId : 1;

                Urunler::create([
                    'kod' => $row[$indexMap['kod']],
                    'isim' => $row[$indexMap['isim']],
                    'cesit' => $row[$indexMap['cesit']],
                    'marka' => $row[$indexMap['marka']],
                    'birim' => $row[$indexMap['birim']],
                    'satis_fiyati' => floatval($row[$indexMap['satis_fiyati']]),
                    'kdv_orani' => intval($row[$indexMap['kdv_orani']]),
                    'stok_miktari' => intval($row[$indexMap['stok_miktari']]),
                    'kritik_stok' => intval($row[$indexMap['kritik_stok']]),
                    'tedarik_fiyati' => floatval($row[$indexMap['tedarik_fiyati']]),
                    'aktif' => filter_var($row[$indexMap['aktif']], FILTER_VALIDATE_BOOLEAN),
                    'tedarikci_id' => $finalTedarikciId,
                ]);
            }

            return response()->json(['message' => 'Ürünler başarıyla yüklendi.']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Yükleme sırasında hata: ' . $e->getMessage()], 500);
        }
    }


    public function export()
    {
        return Excel::download(new UrunlerExport, 'urunler.xlsx');
    }
}