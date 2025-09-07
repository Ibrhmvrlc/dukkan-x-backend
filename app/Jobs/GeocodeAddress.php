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

    public function __construct(public int $adresId)
    {
        // Kuyruk adı burada; $queue property TANIMLAMA!
        $this->onQueue('geocoding');
    }

    public function handle(): void
    {
        $a = TeslimatAdresi::find($this->adresId);
        if (!$a) return;

        // ---- Normalizasyon ----
        $il = trim((string) $a->il);
        $ilceRaw = trim((string) $a->ilce);
        $ilce = (mb_strtoupper($ilceRaw, 'UTF-8') === 'MERKEZ') ? null : $ilceRaw;

        // Kısaltmaları aç
        $street = preg_replace(
            ['/\bMah\.\b/u','/\bCd\.\b/u','/\bSk\.\b/u','/\bNo:\s*/u'],
            ['Mahallesi','Caddesi','Sokak','No '],
            (string) $a->adres
        );
        $street = preg_replace('/\s+/u', ' ', trim($street));

        $postal = $a->posta_kodu ? trim($a->posta_kodu) : null;

        // Yalova viewbox
        $yalovaBox = '28.70,40.45,29.70,40.85'; // minLon,minLat,maxLon,maxLat
        $yalovaCenter = [40.6549, 29.2842];

        // Yardımcılar
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
                return $ilMatch && ($city === $ilU || $county === $ilU);
            }
            return $ilMatch && in_array($ilceU, [$county, $dist, $city], true);
        };

        $haversineKm = function($lat1, $lon1, $lat2, $lon2): float {
            $R = 6371;
            $dLat = deg2rad($lat2 - $lat1);
            $dLon = deg2rad($lon2 - $lon1);
            $a = sin($dLat/2)**2 + cos(deg2rad($lat1))*cos(deg2rad($lat2))*sin($dLon/2)**2;
            return 2 * $R * asin(min(1, sqrt($a)));
        };

        $saveIfValid = function(array $item, string $source, int $confidence, string $hash = null) use ($a, $withinYalova, $haversineKm, $yalovaCenter): bool {
            $lat = isset($item['lat']) ? (float)$item['lat'] : null;
            $lng = isset($item['lon']) ? (float)$item['lon'] : null;
            if (!$lat || !$lng) return false;

            $addr = $item['address'] ?? [];
            if (!$withinYalova($addr)) return false;

            // Yalova merkezine 35km’den uzaksa şüpheli
            $dist = $haversineKm($yalovaCenter[0], $yalovaCenter[1], $lat, $lng);
            if ($dist > 35) return false;

            $a->lat = $lat;
            $a->lng = $lng;
            $a->geocoded_at = now();
            $a->geocode_source = $source;
            $a->geocode_confidence = $confidence;
            if ($hash) $a->geocode_hash = $hash;
            $a->saveQuietly();

            Log::info('[GEOCODE] saved', ['id' => $a->id, 'src' => $source, 'lat' => $lat, 'lng' => $lng]);
            return true;
        };

        // Hash (aynı tam adresi tekrar tekrar çağırmayı önlemek için)
        $hashBase = implode('|', array_filter([$street, $ilce, $il, $postal, 'TR']));
        $hash = sha1($hashBase);

        // 1) Structured
        $structuredParams = [
            'format' => 'json',
            'limit'  => 1,
            'addressdetails' => 1,
            'country' => 'Türkiye',
            'state'   => $il,
            'street'  => $street,
        ];
        if ($ilce) {
            $structuredParams['city'] = $ilce;
            $structuredParams['county'] = $ilce;
        } else {
            $structuredParams['city'] = $il;
        }
        if ($postal) $structuredParams['postalcode'] = $postal;

        $resp = Http::withHeaders([
                'User-Agent' => 'DukkanX/1.0 (iletisim@ornek.com)',
                'Accept-Language' => 'tr',
            ])
            ->timeout(15)
            ->get('https://nominatim.openstreetmap.org/search', $structuredParams);

        if ($resp->ok()) {
            $json = $resp->json();
            if (is_array($json) && !empty($json[0]) && $saveIfValid($json[0], 'nominatim', 90, $hash)) return;
            Log::warning('[GEOCODE] no-result-1', ['id' => $a->id, 'q' => $structuredParams]);
        }

        // 2) Structured (city=Yalova)
        $resp2 = Http::withHeaders([
                'User-Agent' => 'DukkanX/1.0 (iletisim@ornek.com)',
                'Accept-Language' => 'tr',
            ])
            ->timeout(15)
            ->get('https://nominatim.openstreetmap.org/search', array_filter([
                'format' => 'json', 'limit' => 1, 'addressdetails' => 1,
                'country' => 'Türkiye',
                'state' => $il,
                'city' => $il,
                'street' => $street,
                'postalcode' => $postal,
            ]));

        if ($resp2->ok()) {
            $json = $resp2->json();
            if (is_array($json) && !empty($json[0]) && $saveIfValid($json[0], 'nominatim', 80, $hash)) return;
            Log::warning('[GEOCODE] no-result-2', ['id' => $a->id]);
        }

        // 3) Freeform + viewbox
        $q = implode(', ', array_filter([$street, $ilce, $il, 'Türkiye']));
        $resp3 = Http::withHeaders([
                'User-Agent' => 'DukkanX/1.0 (iletisim@ornek.com)',
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
            if (is_array($json) && !empty($json[0]) && $saveIfValid($json[0], 'nominatim', 70, $hash)) return;
            Log::warning('[GEOCODE] no-result-3', ['id' => $a->id, 'q' => $q]);
        }

        // 4) Fallback: ilçe centroid (İLÇE ADINI BÜYÜK HARFLE EŞLE!)
        $centroids = [
            'MERKEZ'      => ['lat' => 40.655100, 'lng' => 29.277200],
            'ÇİFTLİKKÖY'  => ['lat' => 40.684500, 'lng' => 29.320300],
            'ALTINOVA'    => ['lat' => 40.695000, 'lng' => 29.508000],
            'TERMAL'      => ['lat' => 40.615000, 'lng' => 29.167000],
            'ARMUTLU'     => ['lat' => 40.519000, 'lng' => 28.842000],
        ];
        $key = mb_strtoupper($ilce ?? 'Merkez', 'UTF-8');
        if (isset($centroids[$key])) {
            $a->lat = $centroids[$key]['lat'];
            $a->lng = $centroids[$key]['lng'];
            $a->geocoded_at = now();
            $a->geocode_source = 'centroid-fallback';
            $a->geocode_confidence = 50;
            $a->geocode_hash = $hash;
            $a->saveQuietly();
            Log::info('[GEOCODE] saved-fallback', ['id' => $a->id, 'key' => $key]);
            return;
        }

        Log::warning('[GEOCODE] all-attempts-failed', ['id' => $a->id]);
    }
}