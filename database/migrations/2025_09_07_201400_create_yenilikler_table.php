<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('yenilikler', function (Blueprint $table) {
            $table->id();
            $table->string('baslik');
            $table->string('ozet')->nullable();
            $table->text('icerik')->nullable(); // markdown ya da düz metin
            $table->string('modul')->nullable(); // ör: 'Sipariş', 'Müşteri', 'Raporlar'
            $table->enum('seviye', ['info','improvement','fix','breaking'])->default('info');
            $table->string('surum')->nullable(); // ör: 1.4.2
            $table->boolean('is_pinned')->default(false);
            $table->string('link')->nullable(); // detay dokümanı / changelog sayfası
            $table->timestamp('yayin_tarihi')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_pinned', 'yayin_tarihi']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('yenilikler');
    }
};