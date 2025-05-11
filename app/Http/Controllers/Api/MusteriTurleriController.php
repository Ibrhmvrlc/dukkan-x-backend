<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MusteriTur;
use Illuminate\Http\Request;

class MusteriTurleriController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => MusteriTur::where('aktif', true)
                ->orderBy('isim')
                ->get(['id', 'isim'])
        ]);
    }
}
