<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Yetkililer extends Model
{
    use SoftDeletes;

    protected $table = 'yetkililer';

    protected $fillable = ['musteri_id', 'isim', 'telefon', 'email', 'pozisyon'];

    public function musteri()
    {
        return $this->belongsTo(Musteriler::class, 'musteri_id');
    }
}
