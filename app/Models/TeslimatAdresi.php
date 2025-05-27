<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

// app/Models/TeslimatAdresi.php
class TeslimatAdresi extends Model
{
    use SoftDeletes;

    protected $table = 'teslimat_adresleri';

    protected $fillable = ['musteri_id', 'baslik', 'adres', 'ilce', 'il', 'posta_kodu'];

    public function musteri()
    {
        return $this->belongsTo(Musteriler::class);
    }
}