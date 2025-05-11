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
        Schema::create('musteriler', function (Blueprint $table) {
            $table->id();
            $table->string('unvan'); // Firma adı ya da kişi adı
            $table->enum('tur', ['bireysel', 'kurumsal'])->default('bireysel');
            $table->string('vergi_no')->nullable();
            $table->string('vergi_dairesi')->nullable();
            $table->string('telefon')->nullable();
            $table->string('email')->nullable();
            $table->text('adres')->nullable();
            $table->text('notlar')->nullable();
            $table->boolean('aktif')->default(true);
            $table->foreignId('musteri_tur_id')->nullable()->constrained('musteri_turleri')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes(); // Arşivleme için
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('musteriler');
    }
};
