<?php

namespace Database\Seeders;

use App\Models\Yenilik;
use Illuminate\Database\Seeder;

class YenilikSeeder extends Seeder
{
    public function run(): void
    {
        Yenilik::create([
            'baslik' => 'Müşteri Ekstre Sayfası – TR tarih formatı',
            'ozet' => 'Tarih gösterimleri Türkiye formatına çekildi.',
            'icerik' => "Ekstre ve tahsilat sayfalarında **dd.MM.yyyy** formatı kullanılıyor.",
            'modul' => 'Finans',
            'seviye' => 'improvement',
            'surum' => '1.4.2',
            'is_pinned' => true,
            'yayin_tarihi' => now()->subDay(),
            'link' => null,
        ]);

        Yenilik::create([
            'baslik' => 'Sipariş Detay – Yoğunlaştırılmış görünüm',
            'ozet' => 'Ürün listesi tek sayfaya sığacak şekilde sadeleştirildi.',
            'icerik' => "Yetkili/Adres/Not tek satır balonlarda; ürün alanı genişletildi.",
            'modul' => 'Sipariş',
            'seviye' => 'info',
            'surum' => '1.4.1',
            'is_pinned' => false,
            'yayin_tarihi' => now()->subDays(2),
            'link' => null,
        ]);
    }
}