<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TedarikciResource;
use App\Models\Tedarikci;
use Illuminate\Http\Request;

class TedarikciController extends Controller
{
    public function index()
    {
        $tedarikciler = Tedarikci::all();
        return response()->json(['data' => TedarikciResource::collection($tedarikciler)]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'unvan' => 'required|string|max:255',
            'vergi_dairesi' => 'nullable|string|max:255',
            'vergi_no' => 'nullable|string|max:50',
            'adres' => 'nullable|string',
            'yetkili_ad' => 'nullable|string|max:255',
            'telefon' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
        ]);

        $tedarikci = Tedarikci::create($validated);

        return response()->json([
            'message' => 'Tedarikçi başarıyla eklendi.',
            'data' => new TedarikciResource($tedarikci)
        ], 201);
    }

    public function show($id)
    {
        $tedarikci = Tedarikci::findOrFail($id);
        return response()->json([
            'data' => new TedarikciResource($tedarikci)
        ]);
    }

    public function update(Request $request, $id)
    {
        $tedarikci = Tedarikci::findOrFail($id);

        $validated = $request->validate([
            'unvan' => 'required|string|max:255',
            'vergi_dairesi' => 'nullable|string|max:255',
            'vergi_no' => 'nullable|string|max:50',
            'adres' => 'nullable|string',
            'yetkili_ad' => 'nullable|string|max:255',
            'telefon' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
        ]);

        $tedarikci->update($validated);

        return response()->json([
            'message' => 'Tedarikçi başarıyla güncellendi.',
            'data' => new TedarikciResource($tedarikci)
        ]);
    }

    public function destroy($id)
    {
        $tedarikci = Tedarikci::findOrFail($id);
        $tedarikci->delete();

        return response()->json([
            'message' => 'Tedarikçi başarıyla silindi.'
        ]);
    }
}