<?php

namespace App\Http\Controllers;

use App\Models\TeslimatAdresi;
use Illuminate\Http\Request;
use App\Jobs\GeocodeAddress;

class TeslimatAdresiController extends Controller
{
    // Teslimat adresi oluşturma
    public function store(Request $request, $musteriId)
    {
        $validated = $request->validate([
            'baslik'      => 'required|string|max:255',
            'adres'       => 'required|string',
            'ilce'        => 'nullable|string',
            'il'          => 'nullable|string',
            'posta_kodu'  => 'nullable|string',
        ]);

        $validated['musteri_id'] = $musteriId;

        $adres = TeslimatAdresi::create($validated);

        // Kaydettikten sonra geocoding job'ını kuyruga at
        GeocodeAddress::dispatch($adres->id)
            ->onQueue('geocoding')
            ->afterCommit(); // transaction varsa commit sonrası

        // Dönüşte en güncel halini ver (lat/lng job bitmeden dolmaz, ama fresh döndürelim)
        return response()->json($adres->fresh(), 201);
    }

    // Güncelleme
    public function update(Request $request, $musteriId, $adresId)
    {
        $validated = $request->validate([
            'baslik'      => 'required|string|max:255',
            'adres'       => 'required|string',
            'ilce'        => 'nullable|string',
            'il'          => 'nullable|string',
            'posta_kodu'  => 'nullable|string',
        ]);

        $adres = TeslimatAdresi::where('musteri_id', $musteriId)->findOrFail($adresId);
        $adres->update($validated);

        // Güncellemeden sonra da geocoding'i tetikle
        GeocodeAddress::dispatch($adres->id)
            ->onQueue('geocoding')
            ->afterCommit();

        return response()->json($adres->fresh());
    }

    // Silme
    public function destroy($musteriId, $adresId)
    {
        $adres = TeslimatAdresi::where('musteri_id', $musteriId)->findOrFail($adresId);
        $adres->delete();

        // 204 No Content ise gövde dönmeyelim
        return response()->noContent();
    }
}