<?php

namespace App\Http\Controllers\Api;

use App\Exports\UrunlerExport;
use App\Http\Controllers\Controller;
use App\Http\Resources\UrunResource;
use App\Models\Siparis;
use App\Models\Tedarikci;
use App\Models\Urunler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public function grafik(Request $request, int $urunId)
    {
        // ?year=2025 verilirse onu, verilmezse içinde bulunduğumuz yılı kullan
        $year = (int) ($request->query('year') ?: now()->year);

        // siparis_urun (adet) -> siparisler (tarih) join
        $ayToplam = DB::table('siparis_urun')
            ->join('siparisler', 'siparisler.id', '=', 'siparis_urun.siparis_id')
            ->where('siparis_urun.urun_id', $urunId)
            ->whereYear('siparisler.created_at', $year)
            // ->where('siparisler.durum', 'tamamlandi') // (opsiyonel) iptal/iade vb. ayıklamak istersen
            ->when(DB::getSchemaBuilder()->hasColumn('siparisler', 'deleted_at'), function($q){
                $q->whereNull('siparisler.deleted_at'); // soft-delete’leri dışla
            })
            ->selectRaw('MONTH(siparisler.created_at) as ay, COALESCE(SUM(siparis_urun.adet),0) as toplam')
            ->groupBy('ay')
            ->pluck('toplam', 'ay') // [1 => 10, 3 => 5, ...]
            ->toArray();

        // 1..12 tüm ayları doldur (olmayan ay 0)
        $veri = [];
        foreach (range(1, 12) as $ay) {
            $veri[] = (float) ($ayToplam[$ay] ?? 0);
        }

        return response()->json([
            'year' => $year,
            'data' => $veri, // [Ocak..Aralık] 12 elemanlı dizi
        ]);
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

    public function stokEkle(Request $request, $id)
    {
        $urun = Urunler::findOrFail($id);

        $request->validate([
            'miktar' => 'required|integer|min:1'
        ]);

        $urun->stok_miktari += $request->input('miktar');
        $urun->save();

        return response()->json([
            'message' => 'Stok başarıyla eklendi.',
            'stok_miktari' => $urun->stok_miktari
        ]);
    }

    public function updateFiyat(Request $request, Urunler $urun)
    {
        $validated = $request->validate([
            'satis_fiyati'    => ['nullable','numeric','min:0'],
            'tedarik_fiyati'  => ['nullable','numeric','min:0'],
        ]);

        // Sadece gönderilen alanları doldur
        $fields = array_filter($validated, fn($v) => !is_null($v));

        if (empty($fields)) {
            return response()->json(['message' => 'Güncellenecek alan yok.'], 422);
        }

        $urun->fill($fields)->save();

        return response()->json([
            'message' => 'Fiyat(lar) güncellendi',
            'urun'    => $urun,
        ]);
    }

    public function topluGuncelle(Request $request)
    {
        $validated = $request->validate([
            'oran'     => ['required','numeric'], // +5 zam, -5 indirim gibi
            'markalar' => ['array'],
            'hedef'    => ['required','in:satis,tedarik,ikisi'],
        ]);

        $q = Urunler::query();

        if (!empty($validated['markalar'])) {
            $q->whereIn('marka', $validated['markalar']);
        }

        // oran % olarak geliyor: 5 => 1.05, -5 => 0.95
        $factor = 1 + ($validated['oran'] / 100);

        DB::beginTransaction();
        try {
            if ($validated['hedef'] === 'satis' || $validated['hedef'] === 'ikisi') {
                $q->clone()->update([
                    'satis_fiyati' => DB::raw("ROUND(satis_fiyati * {$factor}, 2)")
                ]);
            }
            if ($validated['hedef'] === 'tedarik' || $validated['hedef'] === 'ikisi') {
                $q->clone()->update([
                    'tedarik_fiyati' => DB::raw("ROUND(tedarik_fiyati * {$factor}, 2)")
                ]);
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Toplu işlem hatası', 'error' => $e->getMessage()], 500);
        }

        return response()->json(['message' => 'Toplu güncelleme tamamlandı']);
    }

}