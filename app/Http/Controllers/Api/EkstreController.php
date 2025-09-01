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
        $page     = max(1, (int) $request->query('page', 1));

        // ---------- YARDIMCI: onaylı kriteri (belge_no dolu) ----------
        $approvedStr = function ($col) {
            return fn($q) => $q
                ->whereNotNull($col)
                ->where($col, '!=', '');
        };
        // ---------- YARDIMCI: onaysız kriteri (belge_no boş) ----------
        $pendingStr = function ($col) {
            return fn($q) => $q
                ->where(function ($qq) use ($col) {
                    $qq->whereNull($col)->orWhere($col, '=', '');
                });
        };

        // ---------- SİPARİŞ (BORÇ) - SADECE ONAYLI ----------
        $siparisApproved = $musteri->siparisler()
            ->select(['id', 'tarih', 'fatura_no', 'toplam_tutar'])
            ->when($dateFrom, fn($q) => $q->whereDate('tarih', '>=', $dateFrom))
            ->when($dateTo,   fn($q) => $q->whereDate('tarih', '<=', $dateTo))
            ->where($approvedStr('fatura_no'))
            ->get()
            ->map(function ($s) {
                return [
                    'tur'      => 'siparis',
                    'id'       => $s->id,
                    'tarih'    => optional($s->tarih)->format('Y-m-d'),
                    'aciklama' => 'Sipariş',
                    'belge_no' => $s->fatura_no,
                    'borc'     => (float) $s->toplam_tutar,
                    'alacak'   => 0.0,
                ];
            });

        // ---------- TAHSİLAT (ALACAK) - SADECE ONAYLI ----------
        $tahsilatApproved = $musteri->tahsilatlar()
            ->select(['id','tarih','aciklama','referans_no','tutar'])
            ->when($dateFrom, fn($q) => $q->whereDate('tarih', '>=', $dateFrom))
            ->when($dateTo,   fn($q) => $q->whereDate('tarih', '<=', $dateTo))
            ->where($approvedStr('referans_no'))
            ->get()
            ->map(function ($t) {
                return [
                    'tur'      => 'tahsilat',
                    'id'       => $t->id,
                    'tarih'    => optional($t->tarih)->format('Y-m-d'),
                    'aciklama' => $t->aciklama ?? 'Tahsilat',
                    'belge_no' => $t->referans_no,
                    'borc'     => 0.0,
                    'alacak'   => (float) $t->tutar,
                ];
            });

        // ---------- BİRLEŞTİR + SIRALA (onaylılar) ----------
        $items = $siparisApproved->concat($tahsilatApproved)
            ->sortBy(fn($row) => $row['tarih'] . sprintf('%010d', $row['id']))
            ->values()
            ->all();

        // ---------- KÜMÜLATİF BAKİYE (sadece onaylılardan) ----------
        $running = 0.0;
        foreach ($items as &$row) {
            $running += ($row['borc'] - $row['alacak']);
            $row['bakiye'] = round($running, 2);
        }
        unset($row);

        // ---------- SAYFALAMA (onaylılar üstünden) ----------
        $offset = ($page - 1) * $perPage;
        $paged  = array_slice($items, $offset, $perPage);
        $total  = count($items);

        // ---------- ÖZET (yalnızca onaylı) ----------
        $toplamBorc   = round(array_sum(array_column($items, 'borc')), 2);
        $toplamAlacak = round(array_sum(array_column($items, 'alacak')), 2);
        $bakiye       = round($running, 2);

        // ---------- ONAYSIZ SAYISI (date filtreleriyle) ----------
        $pendingSiparisCount = $musteri->siparisler()
            ->when($dateFrom, fn($q) => $q->whereDate('tarih', '>=', $dateFrom))
            ->when($dateTo,   fn($q) => $q->whereDate('tarih', '<=', $dateTo))
            ->where($pendingStr('fatura_no'))
            ->count();

        $pendingTahsilatCount = $musteri->tahsilatlar()
            ->when($dateFrom, fn($q) => $q->whereDate('tarih', '>=', $dateFrom))
            ->when($dateTo,   fn($q) => $q->whereDate('tarih', '<=', $dateTo))
            ->where($pendingStr('referans_no'))
            ->count();

        $pendingCount = $pendingSiparisCount + $pendingTahsilatCount;

        return response()->json([
            'data' => $paged, // yalnızca onaylılar listelenir
            'meta' => [
                'total'         => $total,
                'per_page'      => $perPage,
                'current_page'  => $page,
                'last_page'     => (int) ceil($total / $perPage),
                // özetler (sadece onaylı)
                'toplam_borc'   => $toplamBorc,
                'toplam_alacak' => $toplamAlacak,
                'bakiye'        => $bakiye,
                // onay bekleyen sayısı (listeye dahil DEĞİL)
                'pending_count' => $pendingCount,
            ],
        ]);
    }
}
