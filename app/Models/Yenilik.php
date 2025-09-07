<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Yenilik extends Model
{
    use SoftDeletes;

    protected $table = 'yenilikler';

    protected $fillable = [
        'baslik','ozet','icerik','modul','seviye','surum',
        'is_pinned','link','yayin_tarihi','created_by'
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'yayin_tarihi' => 'datetime',
    ];

    public function scopeYayinda($q) {
        return $q->whereNotNull('yayin_tarihi')->where('yayin_tarihi','<=',now());
    }

    public function olusturan() {
        return $this->belongsTo(User::class, 'created_by');
    }
}