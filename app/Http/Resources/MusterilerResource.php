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
            'musteri_tur' => $this->segment ? $this->segment->isim : null,
            'not_sayisi' => $this->notlar()->count(),
            'created_at' => $this->created_at->toDateTimeString(),
            'not_sayisi' => $this->notlar_count ?? 0,
        ];
    }
}
