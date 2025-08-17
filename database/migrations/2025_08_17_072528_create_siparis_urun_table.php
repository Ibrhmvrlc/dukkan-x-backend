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
         Schema::create('siparis_urun', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siparis_id')->constrained('siparisler')->cascadeOnDelete();
            $table->foreignId('urun_id')->constrained('urunler')->restrictOnDelete();
            $table->decimal('adet', 12, 3);
            $table->decimal('birim_fiyat', 12, 2)->default(0);
            $table->decimal('iskonto_orani', 5, 2)->default(0);
            $table->decimal('kdv_orani', 5, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('siparis_urun');
    }
};
