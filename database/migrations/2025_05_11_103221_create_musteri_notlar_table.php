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
        Schema::create('musteri_notlar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('musteri_id')->constrained('musteriler')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // notu kim yazdı
            $table->enum('tur', ['not', 'hatirlatici', 'kisitlayici', 'bilgi'])->default('not'); // isteğe göre artırılır
            $table->string('baslik')->nullable(); // opsiyonel başlık
            $table->text('icerik'); // esas mesaj
            $table->dateTime('gecerli_tarih')->nullable(); // hatırlatmalar için geçerlilik
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('musteri_notlar');
    }
};
