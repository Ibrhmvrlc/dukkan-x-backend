<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('urunler', function (Blueprint $table) {
            // 'marka' kolonunu ekliyoruz
            $table->string('marka')->after('cesit');

            // 'tedarikci_id' foreign key kolonunu ekliyoruz ve varsayılan değer olarak 1 atıyoruz
            $table->foreignId('tedarikci_id')->after('aktif')  // ->default(1)
                  ->constrained('tedarikciler')           // foreign key kısıtlamasını belirtiyoruz
                  ->cascadeOnDelete();                   // silme işlemi sırasında cascade (silinen tedarikçi ile ilişkili ürünler de silinsin)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('urunler', function (Blueprint $table) {
            // 'tedarikci_id' foreign key constraint'i ve 'marka' kolonunu kaldırıyoruz
            $table->dropForeign(['tedarikci_id']);
            $table->dropColumn(['marka', 'tedarikci_id']);
        });
    }
};