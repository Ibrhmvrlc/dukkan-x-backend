<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Musteriler extends Model
{
    use SoftDeletes;

    protected $table = 'musteriler';

    protected $casts = [
    'musteri_tur_id' => 'integer',
    ];

    protected $fillable = [
        'unvan', 'tur', 'vergi_no', 'vergi_dairesi',
        'telefon', 'email', 'adres', 'notlar', 'aktif', 'musteri_tur_id', 'iskonto_orani',
    ];

    public function yetkililer()
    {
        return $this->hasMany(Yetkililer::class, 'musteri_id');
    }

    public function notlar()
    {
        return $this->hasMany(MusteriNot::class, 'musteri_id');
    }

    public function tur()
    {
        return $this->belongsTo(MusteriTur::class, 'musteri_tur_id');
    }

    public function musteriTur()
    {
        return $this->belongsTo(MusteriTur::class);
    }

    public function teslimat_adresleri()
    {
        return $this->hasMany(TeslimatAdresi::class, 'musteri_id');
    }

    public function tahsilatlar()
    {
        return $this->hasMany(Tahsilat::class, 'musteri_id');
    }

    public function siparisler()
    {
        return $this->hasMany(Siparis::class, 'musteri_id')
            ->with(['urunler' => function ($q) {
                $q->withPivot(['adet','birim_fiyat','iskonto_orani','kdv_orani']);
            }]);
    }

}
