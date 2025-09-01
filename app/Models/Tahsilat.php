<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tahsilat extends Model
{
    use SoftDeletes;

    protected $table = 'tahsilatlar';

    protected $fillable = [
        'musteri_id', 'tarih', 'tutar', 'kanal', 'referans_no', 'aciklama',
    ];

    protected $casts = [
        'tarih' => 'date:Y-m-d',
        'tutar' => 'decimal:2',
    ];

    public function musteri()
    {
        return $this->belongsTo(Musteriler::class, 'musteri_id');
    }
}
