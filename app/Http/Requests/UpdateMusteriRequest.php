<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMusteriRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('musteri_tur_id')) {
            $this->merge([
                'musteri_tur_id' => is_numeric($this->musteri_tur_id) ? (int) $this->musteri_tur_id : null,
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'unvan' => 'required|string|max:255',
            'tur' => 'required|in:bireysel,kurumsal',
            'vergi_no' => 'nullable|string|max:50',
            'vergi_dairesi' => 'nullable|string|max:100',
            'telefon' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'adres' => 'nullable|string',
            'aktif' => 'boolean',
            'musteri_tur_id' => 'nullable|integer|exists:musteri_turleri,id',
        ];
    }
}