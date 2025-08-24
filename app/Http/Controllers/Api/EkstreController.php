<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Musteriler;
use Illuminate\Http\Request;

class EkstreController extends Controller
{
    public function index(Request $request, Musteriler $musteri)
    {
        $dateFrom = $request->query('date_from'); // YYYY-MM-DD
        $dateTo   = $request->query('date_to');   // YYYY-MM-DD
        $perPage  = (int) ($request->query('per_page', 50));

        // Siparişler (borç)
        $siparisQuery = $musteri->siparisler()
            ->select(['id','tarih','fatura_no'])
            ->when($dateFrom, fn($q) => $q->whereDate('tarih', '>=', $dateFrom))
            ->when($dateTo,   fn($q) => $q->whereDate('tarih', '<=', $dateTo))
            ->get()
            ->map(function ($s) {
                return [
                    'tur'        => 'siparis',
                    'id'         => $s->id,
                    'tarih'      => optional($s->tarih)->format('Y-m-d'),
                    'aciklama'   => 'Sipariş',
                    'belge_no'   => $s->fatura_no ?? $s->id,
                    'borc'       => (float) $s->toplam_tutar,
                    'alacak'     => 0.0,
                ];
            });

        // Tahsilatlar (alacak)
        $tahsilatQuery = $musteri->tahsilatlar()
            ->when($dateFrom, fn($q) => $q->whereDate('tarih', '>=', $dateFrom))
            ->when($dateTo,   fn($q) => $q->whereDate('tarih', '<=', $dateTo))
            ->get()
            ->map(function ($t) {
                return [
                    'tur'        => 'tahsilat',
                    'id'         => $t->id,
                    'tarih'      => optional($t->tarih)->format('Y-m-d'),
                    'aciklama'   => $t->aciklama ?? 'Tahsilat',
                    'belge_no'   => $t->referans_no,
                    'borc'       => 0.0,
                    'alacak'     => (float) $t->tutar,
                ];
            });

        // Birleştir + tarihe göre sırala
        $items = $siparisQuery->concat($tahsilatQuery)
            ->sortBy(fn($row) => $row['tarih'] . sprintf('%010d', $row['id'])) // stabil sıralama
            ->values()
            ->all();

        // Kümülatif bakiye ekle
        $bakiye = 0.0;
        foreach ($items as &$row) {
            $bakiye += ($row['borc'] - $row['alacak']);
            $row['bakiye'] = round($bakiye, 2);
        }

        // Basit manuel sayfalama (isteğe bağlı)
        $page    = max(1, (int) $request->query('page', 1));
        $offset  = ($page - 1) * $perPage;
        $paged   = array_slice($items, $offset, $perPage);
        $total   = count($items);

        return response()->json([
            'data' => $paged,
            'meta' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => (int) ceil($total / $perPage),
                // özetler
                'toplam_borc' => round(array_sum(array_column($items, 'borc')), 2),
                'toplam_alacak' => round(array_sum(array_column($items, 'alacak')), 2),
                'bakiye' => round($bakiye, 2),
            ],
        ]);
    }
}