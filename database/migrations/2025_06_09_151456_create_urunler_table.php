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
        Schema::create('urunler', function (Blueprint $table) {
            $table->id();
            $table->string('kod')->nullable();
            $table->string('isim');
            $table->text('cesit')->nullable();
            $table->string('birim')->default('Adet');
            $table->decimal('satis_fiyati', 10, 2)->default(0);
            $table->integer('kdv_orani')->default(1);
            $table->decimal('stok_miktari', 10, 2)->default(0);
            $table->decimal('kritik_stok', 10, 2)->default(0);
            $table->decimal('tedarik_fiyati', 10, 2)->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('urunler');
    }
};
