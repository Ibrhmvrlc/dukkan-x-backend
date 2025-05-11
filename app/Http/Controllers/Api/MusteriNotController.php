<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMusteriNotRequest;
use App\Models\Musteriler;
use App\Models\MusteriNot;
use Illuminate\Http\Request;

class MusteriNotController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Musteriler $musteri)
    {
        return response()->json([
            'data' => $musteri->notlar()->latest()->get()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMusteriNotRequest $request, Musteriler $musteri)
    {
        $not = $musteri->notlar()->create([
            ...$request->validated(),
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Not başarıyla eklendi.',
            'data' => $not
        ], 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MusteriNot $musteriNot)
    {
         $this->authorize('delete', $musteriNot); // opsiyonel policy

        $musteriNot->delete();

        return response()->json(['message' => 'Not silindi.']);
    }
}