<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Siparis extends Model
{
    use SoftDeletes;

    protected $table = 'siparisler';

    public $timestamps = true; // bu varsa sorun yok

    protected $casts = [
        'tarih' => 'date:Y-m-d',   // <= JSON'da "2025-06-22" olarak döner
    ];

   protected $fillable = [
    'musteri_id',
    'teslimat_adresi_id',
    'yetkili_id',
    'not',
    'fatura_no',
    'tarih'
    ];

    public function musteri()
    {
        return $this->belongsTo(Musteriler::class);
    }

    public function urunler()
    {
        return $this->belongsToMany(Urunler::class, 'siparis_urun', 'siparis_id', 'urun_id')
            ->withPivot(['adet','birim_fiyat','iskonto_orani','kdv_orani'])
            ->withTimestamps();
    }

    public function teslimatAdresi()
    {
        return $this->belongsTo(TeslimatAdresi::class);
    }

    public function yetkili()
    {
        return $this->belongsTo(Yetkililer::class);
    }

    public function recalcTotals(): self
    {
        if (!$this->relationLoaded('urunler')) {
            $this->load(['urunler' => function ($q) {
                $q->withPivot(['adet','birim_fiyat','iskonto_orani','kdv_orani']);
            }]);
        }

        $ara = 0.0; $kdv = 0.0;
        foreach ($this->urunler as $u) {
            $adet = (float)($u->pivot->adet ?? 0);
            $bf   = (float)($u->pivot->birim_fiyat ?? 0);
            $isk  = (float)($u->pivot->iskonto_orani ?? 0);
            $kdvO = (float)($u->pivot->kdv_orani ?? 0);

            $iskK = (1 - $isk/100);
            $satirAra = $adet * $bf * $iskK;          // KDV hariç
            $satirKdv = $satirAra * ($kdvO/100);

            $ara += $satirAra;
            $kdv += $satirKdv;
        }

        $this->ara_toplam   = round($ara, 2);
        $this->kdv_toplam   = round($kdv, 2);
        $this->toplam_tutar = round($ara + $kdv, 2);

        return $this;
    }

    // (İstersen accessor da kalsın; kolondan okur, null ise hesaplar)
    public function getToplamTutarAttribute($val)
    {
        if (!is_null($val)) return (float)$val;
        // yedek hesap
        if (!$this->relationLoaded('urunler')) {
            $this->load('urunler');
        }
        $tmp = (clone $this)->recalcTotals();
        return (float)$tmp->toplam_tutar;
    }
}