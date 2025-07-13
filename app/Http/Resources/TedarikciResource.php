<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TedarikciResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    // app/Http/Resources/TedarikciResource.php
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'unvan' => $this->unvan,
            'vergi_dairesi' => $this->vergi_dairesi,
            'vergi_no' => $this->vergi_no,
            'adres' => $this->adres,
            'yetkili_ad' => $this->yetkili_ad,
            'telefon' => $this->telefon,
            'email' => $this->email,
        ];
    }

}
