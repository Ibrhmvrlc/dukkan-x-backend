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
        Schema::create('yetkililer', function (Blueprint $table) {
            $table->id();
            $table->foreignId('musteri_id')->constrained('musteriler')->onDelete('cascade');
            $table->string('isim');
            $table->string('telefon')->nullable();
            $table->string('email')->nullable();
            $table->string('pozisyon')->nullable();
            $table->timestamps();
        });        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('yetkililer');
    }
};
