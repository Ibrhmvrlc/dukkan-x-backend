<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMusteriRequest;
use App\Http\Requests\UpdateMusteriRequest;
use App\Http\Resources\MusterilerResource;
use App\Models\Musteriler;
use Illuminate\Http\Request;

class MusterilerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request){
        $query = Musteriler::query()->with(['tur'])->with(['musteriTur']);

        // 1. Arama: Unvan, telefon, email alanlarında
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('unvan', 'like', "%{$search}%")
                ->orWhere('telefon', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // 2. Tür filtresi (örnek: filter[tur]=bayii)
        if ($request->has('filter.tur')) {
            $query->whereHas('tur', function ($q) use ($request) {
                $q->where('isim', $request->input('filter.tur'));
            });
        }

        // 3. Sadece aktif olanlar
        if ($request->boolean('only_active', false)) {
            $query->where('aktif', true);
        }

        // 4. Soft delete (silinmişleri de görmek istiyorsan)
        if ($request->boolean('with_deleted', false)) {
            $query->withTrashed();
        }

        // 5. Not sayısını eager load ile getir
        $query->withCount('notlar');

        // 6. Sonuçları döndür
        return MusterilerResource::collection($query->latest()->paginate(15));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMusteriRequest $request)
    {
        $musteri = Musteriler::create($request->validated());
        return new MusterilerResource($musteri->load('musteriTur'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Musteriler $musteriler)
    {
        return new MusterilerResource($musteriler->load('musteriTur', 'yetkililer', 'teslimat_adresleri'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMusteriRequest $request, Musteriler $musteriler)
    {
        $musteriler->update($request->validated());
        return new MusterilerResource($musteriler->load('tur'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Musteriler $musteriler)
    {
        $musteriler->delete();
        return response()->json(['message' => 'Müşteri silindi.']);
    }
}
