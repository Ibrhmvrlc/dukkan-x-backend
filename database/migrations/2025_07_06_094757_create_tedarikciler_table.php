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
        Schema::create('tedarikciler', function (Blueprint $table) {
            $table->id();
            $table->string('unvan');              // Ticari unvan (ör: İçim Süt Ürünleri A.Ş.)
            $table->string('vergi_dairesi');      // Vergi dairesi
            $table->string('vergi_no');           // Vergi numarası
            $table->string('adres');              // Fatura adresi
            $table->string('yetkili_ad');         // Firma yetkilisi adı
            $table->string('telefon')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tedarikciler');
    }
};
