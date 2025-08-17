<?php
// app/Http/Controllers/Api/MusteriFiyatController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Musteriler; // <-- çoğul
use App\Models\Urunler;    // <-- çoğul
use Illuminate\Http\Request;

class MusteriFiyatController extends Controller
{
     public function index(Request $request, int $musteriId)
    {
        $musteri = Musteriler::findOrFail($musteriId);

        $iskonto = max(0, min(100, (float)($musteri->iskonto_orani ?? 0)));

        $perPage = (int) $request->integer('per_page', 25);
        $q       = trim((string) $request->get('q', ''));
        $sort    = in_array($request->get('sort'), ['marka','isim','satis_fiyati']) ? $request->get('sort') : 'marka';
        $dir     = strtolower($request->get('dir')) === 'desc' ? 'desc' : 'asc';

        $query = Urunler::query()
            ->select(['id','isim','marka','satis_fiyati'])
            ->when($q !== '', function ($qb) use ($q) {
                $qb->where(function ($w) use ($q) {
                    $w->where('isim', 'like', "%{$q}%")
                      ->orWhere('marka', 'like', "%{$q}%");
                });
            })
            ->orderBy($sort, $dir)
            ->orderBy('isim', 'asc');

        $paginator = $query->paginate($perPage);

        // Paginator içindeki koleksiyonu dönüştür (ozel_fiyat ekle)
        $paginator->getCollection()->transform(function ($u) use ($iskonto) {
            $ozel = round((float)$u->satis_fiyati * (1 - $iskonto / 100), 2);
            return [
                'id'            => $u->id,
                'isim'          => $u->isim,
                'marka'         => $u->marka,
                'liste_fiyati'  => (float) $u->satis_fiyati,
                'iskonto_orani' => $iskonto,
                'ozel_fiyat'    => $ozel,
            ];
        });

        return response()->json([
            'musteri' => [
                'id'            => $musteri->id,
                'iskonto_orani' => $iskonto,
            ],
            'urunler' => $paginator, // { data: [...], current_page, last_page, per_page, total, from, to, ... }
        ]);
    }
}