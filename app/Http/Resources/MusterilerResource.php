<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MusterilerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'unvan' => $this->unvan,
            'tur' => $this->tur,
            'telefon' => $this->telefon,
            'email' => $this->email,
            'adres' => $this->adres,
            'aktif' => $this->aktif,
            'musteri_tur_id' => [
                'id' => $this->musteriTur?->id,
                'isim' => $this->musteriTur?->isim,
            ],
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
