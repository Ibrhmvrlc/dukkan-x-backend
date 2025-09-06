<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tahsilat;
use App\Models\Musteriler;
use Illuminate\Http\Request;

class TahsilatController extends Controller
{
    public function index($musteriId)
    {
        $tahsilatlar = Tahsilat::where('musteri_id', $musteriId)
            ->orderByDesc('tarih')
            ->paginate(20);

        return response()->json($tahsilatlar);
    }

    public function store(Request $request, $musteriId)
    {
        $validated = $request->validate([
            'tarih' => 'required|date',
            'tutar' => 'required|numeric|min:0.01',
            'kanal' => 'nullable|string|max:255',
            'referans_no' => 'nullable|string|max:255',
            'aciklama' => 'nullable|string|max:500',
        ]);

        $validated['musteri_id'] = $musteriId;

        $tahsilat = Tahsilat::create($validated);

        return response()->json($tahsilat, 201);
    }

    public function destroy($musteriId, $id)
    {
        $tahsilat = Tahsilat::where('musteri_id', $musteriId)->findOrFail($id);
        $tahsilat->delete();

        return response()->json(['message' => 'Tahsilat silindi.']);
    }
}