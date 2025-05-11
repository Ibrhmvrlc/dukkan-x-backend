<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoleController;
use Tymon\JWTAuth\Facades\JWTAuth;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');

Route::get('/roles', [RoleController::class, 'index']);
Route::post('/users/{user}/roles', [RoleController::class, 'assign']); // role ata
Route::get('/users/{user}/roles', [RoleController::class, 'roles']);

Route::get('/me', function (Request $request) {
    try {
        return response()->json([
            'id' => $request->user()->id,
            'name' => $request->user()->name,
            'email' => $request->user()->email,
            'roles' => $request->user()->roles()->pluck('name'),
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
})->middleware('auth:api');

Route::post('/refresh', function () {
    try {
        return response()->json([
            'token' => auth()->refresh()
        ]);
    } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
        return response()->json(['error' => 'Refresh failed'], 401);
    }
})->middleware('auth:api');
