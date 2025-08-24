<?php
// database/migrations/2025_08_24_120000_add_totals_to_siparisler.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up()
    {
        Schema::table('siparisler', function (Blueprint $table) {
            $table->decimal('ara_toplam', 14, 2)->default(0)->after('fatura_no');
            $table->decimal('kdv_toplam', 14, 2)->default(0)->after('ara_toplam');
            $table->decimal('toplam_tutar', 14, 2)->default(0)->after('kdv_toplam');
        });

        // Eski kayıtları geriye dönük doldur (basit PHP hesabı)
        $siparisler = DB::table('siparisler')->pluck('id');
        foreach ($siparisler as $id) {
            // Basit raw SQL ile hesapla
            $satirlar = DB::table('siparis_urun')
                ->where('siparis_id', $id)
                ->select('adet','birim_fiyat','iskonto_orani','kdv_orani')
                ->get();

            $ara = 0; $kdv = 0;
            foreach ($satirlar as $s) {
                $adet = (float)($s->adet ?? 0);
                $bf   = (float)($s->birim_fiyat ?? 0);
                $isk  = (float)($s->iskonto_orani ?? 0);
                $kdvO = (float)($s->kdv_orani ?? 0);

                $iskK = (1 - $isk/100);
                $satirAra = $adet * $bf * $iskK;          // KDV hariç
                $satirKdv = $satirAra * ($kdvO/100);

                $ara += $satirAra;
                $kdv += $satirKdv;
            }
            DB::table('siparisler')->where('id', $id)->update([
                'ara_toplam'    => round($ara, 2),
                'kdv_toplam'    => round($kdv, 2),
                'toplam_tutar'  => round($ara + $kdv, 2),
            ]);
        }
    }

    public function down()
    {
        Schema::table('siparisler', function (Blueprint $table) {
            $table->dropColumn(['ara_toplam','kdv_toplam','toplam_tutar']);
        });
    }
};