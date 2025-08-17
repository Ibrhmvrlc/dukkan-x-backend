<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('siparisler', function (Blueprint $table) {
            // FK varsa önce düşürmeye çalış
            try { $table->dropForeign(['urun_id']); } catch (\Throwable $e) {}

            // Kalemle ilgili kolonları kaldır
            if (Schema::hasColumn('siparisler','urun_id'))       $table->dropColumn('urun_id');
            if (Schema::hasColumn('siparisler','adet'))          $table->dropColumn('adet');
            if (Schema::hasColumn('siparisler','birim_fiyat'))   $table->dropColumn('birim_fiyat');
            if (Schema::hasColumn('siparisler','iskonto_orani')) $table->dropColumn('iskonto_orani');
            if (Schema::hasColumn('siparisler','kdv_orani'))     $table->dropColumn('kdv_orani');
        });
    }

    public function down(): void {
        Schema::table('siparisler', function (Blueprint $table) {
            $table->foreignId('urun_id')->nullable()->constrained('urunler')->nullOnDelete();
            $table->integer('adet')->nullable();
            $table->decimal('birim_fiyat', 10, 2)->nullable();
            $table->decimal('iskonto_orani', 5, 2)->default(0);
            $table->decimal('kdv_orani', 5, 2)->default(10);
        });
    }
};