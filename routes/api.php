<?php

use App\Exports\UrunlerExport;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\Api\MusteriNotController;
use App\Http\Controllers\Api\MusteriTurleriController;
use App\Http\Controllers\Api\MusterilerController;
use App\Http\Controllers\Api\YetkililerController;
use App\Http\Controllers\TeslimatAdresiController;
use App\Http\Controllers\Api\UrunController;
use App\Http\Controllers\Api\SiparisController;
use App\Http\Controllers\Api\TedarikciController;
use Maatwebsite\Excel\Facades\Excel;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/refresh', [AuthController::class, 'refresh']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:api');

Route::middleware('jwt.auth')->group(function () {
    Route::get('/user', fn(Request $request) => $request->user());

    Route::get('/me', fn(Request $request) => response()->json([
        'id' => $request->user()->id,
        'name' => $request->user()->name,
        'email' => $request->user()->email,
        'roles' => $request->user()->roles()->pluck('name'),
    ]));

    Route::get('/roles', [RoleController::class, 'index']);
    Route::post('/users/{user}/roles', [RoleController::class, 'assign']);
    Route::get('/users/{user}/roles', [RoleController::class, 'roles']);

    Route::prefix('v1')->group(function () {
        Route::apiResource('/musteriler', MusterilerController::class);
        Route::apiResource('tedarikciler', TedarikciController::class);
        Route::apiResource('/yetkililer', YetkililerController::class);
        Route::apiResource('/musteriler.teslimat-adresleri', TeslimatAdresiController::class)->only(['store', 'update', 'destroy']);
        
        Route::get('/musteriler/{musteri}/notlar', [MusteriNotController::class, 'index']);
        Route::post('/musteriler/{musteri}/notlar', [MusteriNotController::class, 'store']);
        Route::delete('/musteri-notlar/{musteriNot}', [MusteriNotController::class, 'destroy']);

        Route::get('/musteri-turleri', [MusteriTurleriController::class, 'index']);

        Route::apiResource('/urunler', UrunController::class);
        Route::get('/urunler/{id}/satislar', [UrunController::class, 'grafik']);
        Route::post('/urunler/bulk-upload', [UrunController::class, 'bulkUpload']);

        Route::post('/urunler/export', [UrunController::class, 'export']);

        Route::get('/siparisler/create/{musteri}', [SiparisController::class, 'createWithMusteri']);
        Route::apiResource('/siparisler', SiparisController::class);
        Route::get('/musteriler/{musteri}/siparisler', [SiparisController::class, 'siparislerByMusteri']);
    });
});