<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Urunler extends Model
{
    use HasFactory;

    protected $table = 'urunler';

    protected $fillable = [
        'kod',
        'isim',
        'cesit',
        'birim',
        'satis_fiyati',
        'kdv_orani',
        'stok_miktari',
        'kritik_stok',
        'tedarik_fiyati',
        'aktif',
    ];
}
