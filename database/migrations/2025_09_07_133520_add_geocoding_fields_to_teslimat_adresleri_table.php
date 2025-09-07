<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('teslimat_adresleri', function (Blueprint $table) {
            // 10,7 TR için yeterli hassasiyet (±~1cm). Sonradan doldurulacağı için nullable.
            $table->decimal('lat', 10, 7)->nullable()->after('posta_kodu');
            $table->decimal('lng', 10, 7)->nullable()->after('lat');

            // Jeokodlama meta alanları
            $table->timestamp('geocoded_at')->nullable()->after('lng');
            $table->string('geocode_source', 32)->nullable()->after('geocoded_at');        // nominatim / google / mapbox
            $table->unsignedTinyInteger('geocode_confidence')->nullable()->after('geocode_source'); // 0–100
            $table->string('geocode_hash', 64)->nullable()->after('geocode_confidence');

            // İndeksler
            $table->index(['il', 'deleted_at'], 'teslimat_adresleri_il_deleted_idx');
            $table->index('geocode_hash', 'teslimat_adresleri_geocode_hash_idx');
        });
    }

    public function down(): void
    {
        Schema::table('teslimat_adresleri', function (Blueprint $table) {
            // Önce indeksleri kaldır
            $table->dropIndex('teslimat_adresleri_il_deleted_idx');
            $table->dropIndex('teslimat_adresleri_geocode_hash_idx');

            // Sonra kolonları kaldır
            $table->dropColumn([
                'lat',
                'lng',
                'geocoded_at',
                'geocode_source',
                'geocode_confidence',
                'geocode_hash',
            ]);
        });
    }
};