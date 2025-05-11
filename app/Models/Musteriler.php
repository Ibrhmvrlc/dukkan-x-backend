<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Musteriler extends Model
{
    use SoftDeletes;

    protected $table = 'musteriler';

    protected $fillable = [
        'unvan', 'tur', 'vergi_no', 'vergi_dairesi',
        'telefon', 'email', 'adres', 'notlar', 'aktif'
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
}
