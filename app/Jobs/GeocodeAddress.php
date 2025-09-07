<?php

namespace App\Jobs;

use App\Models\TeslimatAdresi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeocodeAddress implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $queue = 'geocoding';

    public function __construct(public int $adresId) {}

    public function handle(): void
    {
        $a = TeslimatAdresi::find($this->adresId);
        if (!$a) return;

        // ---- Normalizasyon ----
        // "MERKEZ" → il merkez kenti (city=Yalova), ilçe alanını boş geç
        $il = trim((string) $a->il);
        $ilceRaw = trim((string) $a->ilce);
        $ilce = (mb_strtoupper($ilceRaw, 'UTF-8') === 'MERKEZ') ? null : $ilceRaw;

        // Kısaltmaları aç (Mah. → Mahallesi, Cd. → Caddesi, Sk. → Sokak, No: → No)
        $street = preg_replace(
            ['/\bMah\.\b/u','/\bCd\.\b/u','/\bSk\.\b/u','/\bNo:\s*/u'],
            ['Mahallesi','Caddesi','Sokak','No '],
            (string) $a->adres
        );
        $street = preg_replace('/\s+/u', ' ', trim($street));

        $postal = $a->posta_kodu ? trim($a->posta_kodu) : null;

        // Yalova için bounding box (yaklaşık)
        // minLon,minLat,maxLon,maxLat
        $yalovaBox = '28.70,40.45,29.70,40.85';
        $yalovaCenter = [40.6549, 29.2842];

        // Sonucu doğrulayan yardımcılar
        $withinYalova = function(array $addr) use ($il, $ilce): bool {
            $state  = mb_strtoupper($addr['state']  ?? '', 'UTF-8');
            $prov   = mb_strtoupper($addr['province'] ?? '', 'UTF-8');
            $county = mb_strtoupper($addr['county'] ?? '', 'UTF-8');
            $dist   = mb_strtoupper($addr['city_district'] ?? $addr['district'] ?? '', 'UTF-8');
            $city   = mb_strtoupper($addr['city'] ?? $addr['town'] ?? $addr['village'] ?? '', 'UTF-8');

            $ilU   = mb_strtoupper($il, 'UTF-8');
            $ilceU = $ilce ? mb_strtoupper($ilce, 'UTF-8') : null;

            $ilMatch = ($state === $ilU) || ($prov === $ilU);
            if ($ilceU === null) {
                // Merkez ise şehri "YALOVA" kabul et
                return $ilMatch && ($city === $ilU || $county === $ilU);
            }
            // İlçe belirtilmişse county/city_district/district’ten biriyle uyuşsun
            return $ilMatch && in_array($ilceU, [$county, $dist, $city], true);
        };

        $haversineKm = function($lat1, $lon1, $lat2, $lon2): float {
            $R = 6371;
            $dLat = deg2rad($lat2 - $lat1);
            $dLon = deg2rad($lon2 - $lon1);
            $a = sin($dLat/2)**2 + cos(deg2rad($lat1))*cos(deg2rad($lat2))*sin($dLon/2)**2;
            return 2 * $R * asin(min(1, sqrt($a)));
        };

        $saveIfValid = function(array $item) use ($a, $withinYalova, $haversineKm, $yalovaCenter): bool {
            $lat = isset($item['lat']) ? (float)$item['lat'] : null;
            $lng = isset($item['lon']) ? (float)$item['lon'] : null;
            if (!$lat || !$lng) return false;

            $addr = $item['address'] ?? [];
            if (!$withinYalova($addr)) return false;

            // Yalova merkezine 35km’den uzaksa şüpheli (Eskihisar/İzmit sapmalarını keser)
            $dist = $haversineKm($yalovaCenter[0], $yalovaCenter[1], $lat, $lng);
            if ($dist > 35) return false;

            $a->lat = $lat;
            $a->lng = $lng;
            $a->geocoded_at = now();
            $a->geocode_source = 'nominatim';
            $a->geocode_confidence = 90; // basit sabit
            $a->saveQuietly();
            return true;
        };

        // ---- 1) STRUCTURED REQUEST (street/city/state/postalcode) ----
        $structuredParams = [
            'format' => 'json',
            'limit'  => 1,
            'addressdetails' => 1,
            'country' => 'Türkiye',
            'state'   => $il,
            'street'  => $street,
        ];
        // city/ county ayrımı
        if ($ilce) {
            $structuredParams['city'] = $ilce; // TR’de çoğu zaman iş görüyor
            $structuredParams['county'] = $ilce;
        } else {
            $structuredParams['city'] = $il; // Merkez ise "city=Yalova"
        }
        if ($postal) $structuredParams['postalcode'] = $postal;

        $resp = Http::withHeaders([
                'User-Agent' => 'DukkanX/1.0 (varelci.i@gmail.com)',
                'Accept-Language' => 'tr',
            ])
            ->timeout(15)
            ->get('https://nominatim.openstreetmap.org/search', $structuredParams);

        if ($resp->ok()) {
            $json = $resp->json();
            if (is_array($json) && !empty($json[0]) && $saveIfValid($json[0])) {
                return; // kaydedildi
            }
        }

        // ---- 2) STRUCTURED (yalnızca city=Yalova + street + postalcode) ----
        $resp2 = Http::withHeaders([
                'User-Agent' => 'DukkanX/1.0 (iletisim@ornek.com)',
                'Accept-Language' => 'tr',
            ])
            ->timeout(15)
            ->get('https://nominatim.openstreetmap.org/search', array_filter([
                'format' => 'json', 'limit' => 1, 'addressdetails' => 1,
                'country' => 'Türkiye',
                'state' => $il,
                'city' => $il,       // Merkez kabul edip şehri Yalova tut
                'street' => $street,
                'postalcode' => $postal,
            ]));

        if ($resp2->ok()) {
            $json = $resp2->json();
            if (is_array($json) && !empty($json[0]) && $saveIfValid($json[0])) {
                return;
            }
        }

        // ---- 3) FREEFORM + VIEWBOX (Yalova ile sınırla) ----
        $q = implode(', ', array_filter([$street, $ilce, $il, 'Türkiye']));
        $resp3 = Http::withHeaders([
                'User-Agent' => 'DukkanX/1.0 (varelci.i@gmail.com)',
                'Accept-Language' => 'tr',
            ])
            ->timeout(15)
            ->get('https://nominatim.openstreetmap.org/search', [
                'format' => 'json',
                'limit'  => 1,
                'addressdetails' => 1,
                'countrycodes' => 'tr',
                'viewbox' => $yalovaBox,
                'bounded' => 1,
                'q' => $q,
            ]);

        if ($resp3->ok()) {
            $json = $resp3->json();
            if (is_array($json) && !empty($json[0]) && $saveIfValid($json[0])) {
                return;
            }
        }

        // ---- Olmadı: ilçe merkezine fallback (bizim centroid tablosu/haritadaki ile eşleşik) ----
        $centroids = [
            'Merkez'     => ['lat' => 40.655100, 'lng' => 29.277200],
            'Çiftlikköy' => ['lat' => 40.684500, 'lng' => 29.320300],
            'Altınova'   => ['lat' => 40.695000, 'lng' => 29.508000],
            'Termal'     => ['lat' => 40.615000, 'lng' => 29.167000],
            'Armutlu'    => ['lat' => 40.519000, 'lng' => 28.842000],
        ];
        $key = $ilce ?: 'Merkez';
        if (isset($centroids[$key])) {
            $a->lat = $centroids[$key]['lat'];
            $a->lng = $centroids[$key]['lng'];
            $a->geocoded_at = now();
            $a->geocode_source = 'centroid-fallback';
            $a->geocode_confidence = 50;
            $a->saveQuietly();
        }
    }
}
