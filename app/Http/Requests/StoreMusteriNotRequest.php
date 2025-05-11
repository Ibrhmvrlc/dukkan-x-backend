<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMusteriNotRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tur' => 'required|in:not,hatirlatici,kisitlayici,bilgi',
            'baslik' => 'nullable|string|max:255',
            'icerik' => 'required|string',
            'gecerli_tarih' => 'nullable|date',
            'aktif' => 'boolean'
        ];
    }
}
