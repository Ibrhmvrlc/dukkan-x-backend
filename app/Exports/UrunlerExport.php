<?php

namespace App\Exports;

use App\Models\Urunler;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

class UrunlerExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        // eager load ile tedarikçi unvanı çekiliyor
        return Urunler::with('tedarikci')
            ->select([
                'id', 'kod', 'isim', 'cesit', 'marka', 'birim',
                'satis_fiyati', 'kdv_orani',
                'stok_miktari', 'kritik_stok',
                'tedarik_fiyati', 'aktif', 'tedarikci_id'
            ])
            ->get()
            ->map(function ($urun) {
                return [
                    'ID' => $urun->id,
                    'Kod' => $urun->kod,
                    'İsim' => $urun->isim,
                    'Çeşit' => $urun->cesit,
                    'Marka' => $urun->marka,
                    'Birim' => $urun->birim,
                    'Satış Fiyatı' => (float) $urun->satis_fiyati,
                    'KDV Oranı' => $urun->kdv_orani,
                    'Stok Miktarı' => (float) $urun->stok_miktari,
                    'Kritik Stok' => (float) $urun->kritik_stok,
                    'Tedarik Fiyatı' => (float) $urun->tedarik_fiyati,
                    'Aktif' => $urun->aktif ? 'Aktif' : 'Pasif',
                    'Tedarikçi' => $urun->tedarikci?->unvan ?? 'Bilinmiyor',
                ];
            });
    }

    public function headings(): array
    {
        return [
            'ID',
            'Kod',
            'İsim',
            'Çeşit',
            'Marka',
            'Birim',
            'Satış Fiyatı (₺)',
            'KDV Oranı (%)',
            'Stok Miktarı',
            'Kritik Stok',
            'Tedarik Fiyatı (₺)',
            'Aktiflik Durumu',
            'Tedarikçi Ünvanı'
        ];
    }
}