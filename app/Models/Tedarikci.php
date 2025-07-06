<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tedarikci extends Model
{
    protected $table = 'tedarikciler';

     protected $fillable = [
        'unvan',
        'vergi_dairesi',
        'vergi_no',
        'adres',
        'yetkili_ad',
        'telefon',
        'email',
    ];

    public function urunler()
    {
        return $this->hasMany(Urunler::class);
    }
}
