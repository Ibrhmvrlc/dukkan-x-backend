<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Siparis extends Model
{
    use SoftDeletes;

    protected $table = 'siparisler';

    public $timestamps = true; // bu varsa sorun yok

    protected $fillable = [
        'musteri_id',
        'urun_id',
        'teslimat_adresi_id',
        'yetkili_id',
        'not',
        'fatura_no',
        'tarih',
        'adet',
        'birim_fiyat',
        'iskonto_orani',
        'kdv_orani',
    ];

    public function musteri()
    {
        return $this->belongsTo(Musteriler::class);
    }

    public function urun()
    {
        return $this->belongsTo(Urunler::class);
    }

    public function teslimatAdresi()
    {
        return $this->belongsTo(TeslimatAdresi::class);
    }

    public function yetkili()
    {
        return $this->belongsTo(Yetkililer::class);
    }
}