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
            'vergi_no' => $this->vergi_no,
            'vergi_dairesi' => $this->vergi_dairesi,
            'tur' => $this->tur,
            'telefon' => $this->telefon,
            'email' => $this->email,
            'adres' => $this->adres,
            'aktif' => $this->aktif,
            // Select input için ID
            'musteri_tur_id' => $this->musteri_tur_id,

            // Görüntüleme için ilişkili veri
            'musteri_tur' => [
                'id' => $this->musteriTur?->id,
                'isim' => $this->musteriTur?->isim,
            ],
            'yetkililer' => $this->yetkililer->map(function ($yetkili) {
                return [
                    'id' => $yetkili->id,
                    'isim' => $yetkili->isim,
                    'musteri_id' => $yetkili->musteri_id,
                    'telefon' => $yetkili->telefon,
                    'email' => $yetkili->email,
                    'pozisyon' => $yetkili->pozisyon,
                ];
            }),
            'teslimat_adresleri' => $this->whenLoaded('teslimat_adresleri', function () {
                return $this->teslimat_adresleri->map(function ($adres) {
                    return [
                        'id' => $adres->id,
                        'baslik' => $adres->baslik,
                        'adres' => $adres->adres,
                        'ilce' => $adres->ilce,
                        'il' => $adres->il,
                        'posta_kodu' => $adres->posta_kodu,
                    ];
                });
            }),

            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
