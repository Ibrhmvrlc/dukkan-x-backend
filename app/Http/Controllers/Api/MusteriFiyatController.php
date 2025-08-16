<?php
// app/Http/Controllers/Api/MusteriFiyatController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Musteriler; // <-- çoğul
use App\Models\Urunler;    // <-- çoğul
use Illuminate\Http\Request;

class MusteriFiyatController extends Controller
{
    public function index(int $musteriId, Request $request)
    {
        // Müşteriyi ID ile bul
        $musteri = Musteriler::findOrFail($musteriId);

        // Güvenli iskonto aralığı
        $iskonto = max(0, min(100, (float)($musteri->iskonto_orani ?? 0)));

        // Ürünleri çek ve özel fiyatı hesapla
        $urunler = Urunler::select(['id', 'isim', 'marka', 'satis_fiyati'])
            ->orderBy('marka')
            ->orderBy('isim')
            ->get()
            ->map(function ($u) use ($iskonto) {
                $ozel = round((float)$u->satis_fiyati * (1 - $iskonto / 100), 2);

                return [
                    'id'            => $u->id,
                    'isim'          => $u->isim,
                    'marka'         => $u->marka,
                    'liste_fiyati'  => (float)$u->satis_fiyati,
                    'iskonto_orani' => $iskonto,
                    'ozel_fiyat'    => $ozel,
                ];
            })
            ->values();

        return response()->json([
            'musteri' => [
                'id'            => $musteri->id,
                'iskonto_orani' => $iskonto,
            ],
            'urunler' => $urunler,
        ]);
    }
}