<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UrunResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kod' => $this->kod,
            'isim' => $this->isim,
            'cesit' => $this->cesit,
            'birim' => $this->birim,
            'satis_fiyati' => $this->satis_fiyati,
            'tedarik_fiyati' => $this->tedarik_fiyati,
            'kdv_orani' => $this->kdv_orani,
            'stok_miktari' => $this->stok_miktari,
            'kritik_stok' => $this->kritik_stok,
            'aktif' => $this->aktif,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}