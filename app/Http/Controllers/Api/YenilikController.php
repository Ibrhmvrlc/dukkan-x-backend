<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Yenilik;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class YenilikController extends Controller
{
    // GET /api/v1/yenilikler
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);
        $modul   = $request->input('modul');   // optional filtre
        $seviye  = $request->input('seviye');  // optional filtre

        $q = Yenilik::query()
            ->yayinda()
            ->when($modul,  fn($qq) => $qq->where('modul', $modul))
            ->when($seviye, fn($qq) => $qq->where('seviye', $seviye))
            ->orderByDesc('is_pinned')
            ->orderByDesc('yayin_tarihi')
            ->orderByDesc('id');

        return response()->json($q->paginate($perPage));
    }

    // POST /api/v1/yenilikler
    public function store(Request $request)
    {
        $data = $request->validate([
            'baslik' => ['required','string','max:255'],
            'ozet' => ['nullable','string','max:500'],
            'icerik' => ['nullable','string'],
            'modul' => ['nullable','string','max:100'],
            'seviye' => ['required', Rule::in(['info','improvement','fix','breaking'])],
            'surum' => ['nullable','string','max:50'],
            'is_pinned' => ['boolean'],
            'link' => ['nullable','url','max:500'],
            'yayin_tarihi' => ['nullable','date'],
        ]);

        $data['created_by'] = $request->user()?->id;
        $yenilik = Yenilik::create($data);

        return response()->json($yenilik, 201);
    }

    // PUT /api/v1/yenilikler/{id}
    public function update(Request $request, Yenilik $yenilik)
    {
        $data = $request->validate([
            'baslik' => ['sometimes','string','max:255'],
            'ozet' => ['sometimes','nullable','string','max:500'],
            'icerik' => ['sometimes','nullable','string'],
            'modul' => ['sometimes','nullable','string','max:100'],
            'seviye' => ['sometimes', Rule::in(['info','improvement','fix','breaking'])],
            'surum' => ['sometimes','nullable','string','max:50'],
            'is_pinned' => ['sometimes','boolean'],
            'link' => ['sometimes','nullable','url','max:500'],
            'yayin_tarihi' => ['sometimes','nullable','date'],
        ]);

        $yenilik->update($data);
        return response()->json($yenilik);
    }

    // DELETE /api/v1/yenilikler/{id}
    public function destroy(Yenilik $yenilik)
    {
        $yenilik->delete();
        return response()->json(['ok' => true]);
    }
}