<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('tahsilatlar', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('musteri_id');
            $table->date('tarih');
            $table->decimal('tutar', 14, 2);
            $table->string('kanal')->nullable();         // Nakit, Havale/EFT, Kredi Kartı vs
            $table->string('referans_no')->nullable();   // Dekont/Fiş no vb.
            $table->string('aciklama')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('musteri_id')
                  ->references('id')->on('musteriler')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tahsilatlar');
    }
};
