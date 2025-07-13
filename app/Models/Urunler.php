<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Urunler extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'urunler';

    protected $fillable = [
        'kod',
        'teslimat_adresi_id',
        'isim',
        'cesit',
        'marka',
        'birim',
        'satis_fiyati',
        'tedarik_fiyati',
        'stok_miktari',
        'kritik_stok',
        'kdv_orani',
        'aktif',
        'tedarikci_id',
    ];

    public function siparisler()
    {
        return $this->hasMany(Siparis::class, 'urun_id');
    }

    public function tedarikci()
    {
        return $this->belongsTo(Tedarikci::class);
    }
}