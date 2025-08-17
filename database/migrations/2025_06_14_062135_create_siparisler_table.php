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
        Schema::create('siparisler', function (Blueprint $table) {
            $table->id();

            $table->foreignId('musteri_id')->constrained('musteriler')->cascadeOnDelete();
            $table->foreignId('urun_id')->constrained('urunler')->cascadeOnDelete();
            $table->foreignId('teslimat_adresi_id')->nullable()->constrained('teslimat_adresleri')->nullOnDelete();
            $table->foreignId('yetkili_id')->nullable()->constrained('yetkililer')->nullOnDelete();

            $table->string('fatura_no')->nullable(); // Fatura kesildiyse numarası
            $table->date('tarih');

            $table->integer('adet');
            $table->decimal('birim_fiyat', 10, 2);
            
            $table->decimal('iskonto_orani', 5, 2)->default(0); // yüzde olarak (%10 için 10.00)
            $table->decimal('kdv_orani', 5, 2)->default(10);     // yüzde olarak (%10 için 10.00)

            $table->timestamps();
            $table->softDeletes(); // Soft delete sütunu eklendi
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('siparisler');
    }
};