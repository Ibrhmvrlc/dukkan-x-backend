<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MusteriTur extends Model
{

    protected $table = 'musteri_turleri';

    protected $fillable = ['isim', 'aciklama', 'aktif'];

    public function musteriler()
    {
        return $this->hasMany(Musteriler::class, 'musteri_tur_id');
    }
}