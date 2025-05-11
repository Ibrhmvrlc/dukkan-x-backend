<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MusteriNot extends Model
{
    use SoftDeletes;

    protected $table = 'musteri_notlar';

    protected $fillable = [
        'musteri_id', 'user_id', 'tur', 'baslik', 'icerik', 'gecerli_tarih', 'aktif'
    ];

    public function musteri()
    {
        return $this->belongsTo(Musteriler::class, 'musteri_id');
    }

    public function kullanici()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
