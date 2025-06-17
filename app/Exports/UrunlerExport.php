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
        return Urunler::select([
            'id', 'kod', 'isim', 'cesit', 'birim',
            'satis_fiyati', 'kdv_orani',
            'stok_miktari', 'kritik_stok',
            'tedarik_fiyati', 'aktif'
        ])->get()->map(function ($urun) {
            return [
                'ID' => $urun->id,
                'Kod' => $urun->kod,
                'İsim' => $urun->isim,
                'Çeşit' => $urun->cesit,
                'Birim' => $urun->birim,
                'Satış Fiyatı' => (float) $urun->satis_fiyati,
                'KDV Oranı' => $urun->kdv_orani,
                'Stok Miktarı' => (float) $urun->stok_miktari,
                'Kritik Stok' => (float) $urun->kritik_stok,
                'Tedarik Fiyatı' => (float) $urun->tedarik_fiyati,
                'Aktif' => $urun->aktif ? '1' : '0',
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
            'Birim',
            'Satış Fiyatı (₺)',
            'KDV Oranı (%)',
            'Stok Miktarı',
            'Kritik Stok',
            'Tedarik Fiyatı (₺)',
            'Aktif'
        ];
    }
}
