<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Yetkililer;
use App\Models\Musteriler;
use Illuminate\Http\Request;

class YetkililerController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'musteri_id' => 'required|exists:musterilers,id',
            'isim' => 'required|string|max:255',
            'telefon' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'pozisyon' => 'nullable|string|max:255',
        ]);

        $yetkili = Yetkililer::create($validated);
        return response()->json($yetkili, 201);
    }

    public function update(Request $request, $id)
    {
        $yetkili = Yetkililer::findOrFail($id);

        $validated = $request->validate([
            'isim' => 'required|string|max:255',
            'telefon' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'pozisyon' => 'nullable|string|max:255',
        ]);

        $yetkili->update($validated);
        return response()->json($yetkili);
    }

    public function destroy($id)
    {
        $yetkili = Yetkililer::findOrFail($id);
        $yetkili->delete();
        return response()->json(['message' => 'Yetkili silindi.']);
    }
}
