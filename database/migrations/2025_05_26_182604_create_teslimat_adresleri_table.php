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
        Schema::create('teslimat_adresleri', function (Blueprint $table) {
            $table->id();

            // Doğru foreign key tanımı:
            $table->foreignId('musteri_id')->constrained('musteriler')->onDelete('cascade');

            $table->string('adres');
            $table->string('ilce')->nullable();
            $table->string('il')->nullable();
            $table->string('posta_kodu')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teslimat_adresleri');
    }
};
