<?php

namespace App\Http\Controllers;

use App\Models\TeslimatAdresi;
use App\Models\Musteriler;
use Illuminate\Http\Request;

class TeslimatAdresiController extends Controller
{
    // Teslimat adresi oluşturma
    public function store(Request $request, $musteriId)
    {
        $validated = $request->validate([
            'baslik' => 'required|string|max:255',
            'adres' => 'required|string',
            'ilce' => 'nullable|string',
            'il' => 'nullable|string',
            'posta_kodu' => 'nullable|string',
        ]);

        $validated['musteri_id'] = $musteriId;

        $adres = TeslimatAdresi::create($validated);

        return response()->json($adres, 201);
    }

    // Güncelleme
    public function update(Request $request, $musteriId, $adresId)
    {
        $validated = $request->validate([
            'baslik' => 'required|string|max:255',
            'adres' => 'required|string',
            'ilce' => 'nullable|string',
            'il' => 'nullable|string',
            'posta_kodu' => 'nullable|string',
        ]);

        $adres = TeslimatAdresi::where('musteri_id', $musteriId)->findOrFail($adresId);
        $adres->update($validated);

        return response()->json($adres);
    }

    // Silme
    public function destroy($musteriId, $adresId)
    {
        $adres = TeslimatAdresi::where('musteri_id', $musteriId)->findOrFail($adresId);
        $adres->delete();

        return response()->json(['message' => 'Silindi'], 204);
    }
}