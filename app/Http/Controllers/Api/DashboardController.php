<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Musteriler;
use App\Models\Siparis;
use App\Models\Tahsilat;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function ecommerceMetrics()
    {
        $now = Carbon::now();
        $startThis = $now->copy()->startOfMonth();
        $endThis   = $now->copy()->endOfMonth();

        $startPrev = $now->copy()->subMonth()->startOfMonth();
        $endPrev   = $now->copy()->subMonth()->endOfMonth();

        // Müşteri kümülatif (mevcutta aktif olanlar sayılır)
        $customersCurrent = Musteriler::where('created_at', '<=', $endThis)->count();
        $customersPrev    = Musteriler::where('created_at', '<=', $endPrev)->count();

        // Bu ay & geçen ay sipariş adetleri
        $ordersCurrent = Siparis::whereBetween('created_at', [$startThis, $endThis])->count();
        $ordersPrev    = Siparis::whereBetween('created_at', [$startPrev, $endPrev])->count();

        // TÜM ZAMANLAR toplam sipariş adedi
        $ordersTotal   = Siparis::count();

        return response()->json([
            'customers'   => ['current' => $customersCurrent, 'prev' => $customersPrev],
            'orders'      => ['current' => $ordersCurrent,    'prev' => $ordersPrev],
            'ordersTotal' => $ordersTotal,
        ]);
    }

    public function financeMonthly()
    {
        $now       = Carbon::now(); // Europe/Istanbul
        $startThis = $now->copy()->startOfMonth();
        $endThis   = $now->copy()->endOfMonth();

        // <-- EK: Geçen ay aralığı
        $startPrev = $now->copy()->subMonth()->startOfMonth();
        $endPrev   = $now->copy()->subMonth()->endOfMonth();

        // --- Tüm zamanlar ---
        // Eloquent (SoftDeletes) silinmişleri otomatik dışlar.
        $totalInvoicedAll = (float) Siparis::query()
            ->whereNotNull('fatura_no') // faturalı satış
            ->sum('toplam_tutar');

        $totalCollectedAll = (float) Tahsilat::query()->sum('tutar');

        // Borç = Satış - Tahsilat (0'ın altına düşmesin)
        $totalReceivableNow = max($totalInvoicedAll - $totalCollectedAll, 0.0);

        // Genel tahsilat oranı %
        $overallCollectionRatio = $totalInvoicedAll > 0
            ? ($totalCollectedAll / $totalInvoicedAll) * 100
            : 0.0;

        // --- Bu ay & geçen ay tahsilat ---
        // Not: 'tarih' alanın DATE ise -> whereBetween('tarih', [$startThis->toDateString(), $endThis->toDateString()])
        $collectedThisMonth = (float) Tahsilat::query()
            ->whereBetween('tarih', [$startThis, $endThis])
            ->sum('tutar');

        $collectedPrevMonth = (float) Tahsilat::query()
            ->whereBetween('tarih', [$startPrev, $endPrev])
            ->sum('tutar');

        // (Opsiyonel) Yüzde değişimi backend'te de ver
        $monthlyChangePct = $collectedPrevMonth == 0
            ? ($collectedThisMonth > 0 ? 100.0 : 0.0)
            : (($collectedThisMonth - $collectedPrevMonth) / $collectedPrevMonth) * 100.0;

        return response()->json([
            'overall_collection_ratio'       => round($overallCollectionRatio, 2),
            'total_invoiced_all'             => round($totalInvoicedAll, 2),   // Satış (Toplam)
            'total_collected_all'            => round($totalCollectedAll, 2),  // Tahsilat (Toplam)
            'total_receivable_now'           => round($totalReceivableNow, 2), // (opsiyonel)
            'collected_this_month'           => round($collectedThisMonth, 2), // Bu ay tahsilat
            'collected_prev_month'           => round($collectedPrevMonth, 2), // <-- EK
            'monthly_collection_change_pct'  => round($monthlyChangePct, 2),   // <-- Opsiyonel
        ]);
    }

    public function monthlySales()
    {
        // 'Europe/Istanbul' ve Türkçe locale önerilir
        Carbon::setLocale('tr');

        $to   = Carbon::now()->endOfMonth();             // içinde bulunduğumuz ayın son günü
        $from = $to->copy()->subYear()->startOfMonth();  // geçen yılın aynı ayının ilk günü

        // SoftDeletes global scope: deleted_at IS NULL otomatik
        $rows = Siparis::query()
            ->whereNotNull('fatura_no') // sadece faturalı siparişler satış sayılır
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m-01") as ym, SUM(toplam_tutar) as total')
            ->groupBy('ym')
            ->pluck('total', 'ym'); // ["2024-09-01" => 12345, ...]

        // from..to arası AY AY (her ikisi dahil)
        $labels = [];
        $totals = [];
        $cursor = $from->copy();
        while ($cursor->lte($to)) {
            $labels[] = $cursor->isoFormat('MMM');  // "Eyl", "Eki", ...
            $key = $cursor->format('Y-m-01');
            $totals[] = (float) ($rows[$key] ?? 0.0);
            $cursor->addMonth();
        }

        return response()->json([
            'from'   => $from->toDateString(), // "2024-09-01"
            'to'     => $to->toDateString(),   // "2025-09-30"
            'labels' => $labels,               // 13 öğe (Eyl..Eyl)
            'totals' => $totals,               // 13 öğe
        ]);
    }

    public function monthlyCollections()
    {
        Carbon::setLocale('tr');

        $to   = Carbon::now()->endOfMonth();            // bu ay sonu
        $from = $to->copy()->subYear()->startOfMonth(); // geçen yıl aynı ay başı (ikisi dahil)

        // SoftDeletes global scope silinmişleri otomatik dışlar
        $rows = Tahsilat::query()
            ->whereBetween('tarih', [$from, $to]) // tarih: ödeme tarihi (DATE/DATETIME)
            ->selectRaw('DATE_FORMAT(tarih, "%Y-%m-01") as ym, SUM(tutar) as total')
            ->groupBy('ym')
            ->pluck('total', 'ym'); // ["2024-09-01" => 12345.67, ...]

        $labels = [];
        $totals = [];
        $cursor = $from->copy();
        while ($cursor->lte($to)) {
            $labels[] = $cursor->isoFormat('MMM'); // "Eyl","Eki",...
            $key = $cursor->format('Y-m-01');
            $totals[] = (float) ($rows[$key] ?? 0.0);
            $cursor->addMonth();
        }

        return response()->json([
            'from'   => $from->toDateString(),
            'to'     => $to->toDateString(),
            'labels' => $labels, // 13 öğe
            'totals' => $totals, // 13 öğe
        ]);
    }

    public function yalovaDeliveryPoints()
    {
        // Yalova ilçe merkezleri için basit fallback (lat/lng boşsa)
        $centroids = [
            'Merkez'      => ['lat' => 40.655100, 'lng' => 29.277200],
            'Çiftlikköy'  => ['lat' => 40.684500, 'lng' => 29.320300],
            'Altınova'    => ['lat' => 40.695000, 'lng' => 29.508000],
            'Termal'      => ['lat' => 40.615000, 'lng' => 29.167000],
            'Armutlu'     => ['lat' => 40.519000, 'lng' => 28.842000],
        ];

        $rows = DB::table('teslimat_adresleri as ta')
            ->leftJoin('siaprisler as s', function ($j) {
                $j->on('s.teslimat_adresi_id', '=', 'ta.id')
                ->whereNull('s.deleted_at'); // iptal sevkiyatlar hariç
            })
            ->whereNull('ta.deleted_at')   // soft delete adresler hariç
            ->where('ta.il', 'Yalova')     // sadece Yalova
            ->select(
                'ta.id',
                'ta.baslik',               // <-- FE için
                'ta.adres',
                'ta.ilce',
                'ta.il',
                'ta.lat',
                'ta.lng',
                DB::raw('COUNT(s.id) as shipments_count')
            )
            ->groupBy('ta.id', 'ta.baslik', 'ta.adres', 'ta.ilce', 'ta.il', 'ta.lat', 'ta.lng')
            ->orderByDesc(DB::raw('COUNT(s.id)'))
            ->get();

        // lat/lng boş ise ilçe merkez koordinatları ile doldur
        $points = $rows->map(function ($r) use ($centroids) {
            if (is_null($r->lat) || is_null($r->lng)) {
                $ilce = $r->ilce ?? 'Merkez';
                if (isset($centroids[$ilce])) {
                    $r->lat = $centroids[$ilce]['lat'];
                    $r->lng = $centroids[$ilce]['lng'];
                }
            }
            // FE tipine uyacak şekilde 'baslik' adını koruyoruz
            return [
                'id'               => $r->id,
                'baslik'           => $r->baslik, // varsa özel başlık
                'adres'            => $r->adres,
                'ilce'             => $r->ilce,
                'il'               => $r->il,
                'lat'              => (float) $r->lat,
                'lng'              => (float) $r->lng,
                'shipments_count'  => (int) $r->shipments_count,
            ];
        });

        return response()->json(['points' => $points]);
    }
}