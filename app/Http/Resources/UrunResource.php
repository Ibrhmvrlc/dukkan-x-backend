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
            'marka' => $this->marka,
            'birim' => $this->birim,
            'satis_fiyati' => $this->satis_fiyati,
            'tedarik_fiyati' => $this->tedarik_fiyati,
            'stok_miktari' => $this->stok_miktari,
            'kritik_stok' => $this->kritik_stok,
            'kdv_orani' => $this->kdv_orani,
            'aktif' => $this->aktif,
            'tedarikci_id' => $this->tedarikci_id,

            // Ek olarak tedarikçi bilgisi (eager load edilmişse)
            'tedarikci' => $this->whenLoaded('tedarikci', function () {
                return [
                    'id' => $this->tedarikci->id,
                    'unvan' => $this->tedarikci->unvan,
                    'vergi_no' => $this->tedarikci->vergi_no,
                    'vergi_dairesi' => $this->tedarikci->vergi_dairesi,
                    'adres' => $this->tedarikci->adres,
                    'yetkili_ad' => $this->tedarikci->yetkili_ad,
                    'telefon' => $this->tedarikci->telefon,
                    'email' => $this->tedarikci->email,
                ];
            }),
        ];
    }
}