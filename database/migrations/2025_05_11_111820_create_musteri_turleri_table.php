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
        Schema::create('musteri_turleri', function (Blueprint $table) {
            $table->id();
            $table->string('isim'); // örnek: Bayii, Kurumsal, vb.
            $table->string('aciklama')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('musteri_turleri');
    }
};
