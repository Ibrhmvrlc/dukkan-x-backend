<?php

namespace App\Observers;

use App\Models\TeslimatAdresi;
use App\Jobs\GeocodeAddress;
use Illuminate\Support\Facades\Log;

class TeslimatAdresiObserver
{
    public function saved(TeslimatAdresi $a): void
    {
        if (
            $a->wasRecentlyCreated ||
            $a->wasChanged(['adres','ilce','il']) ||
            empty($a->lat) || empty($a->lng)
        ) {
            Log::info('[GEOCODE] dispatch', ['id' => $a->id]);
            GeocodeAddress::dispatch($a->id)
                ->onQueue('geocoding')
                ->afterCommit(); // <-- kritik
        }
    }
}
