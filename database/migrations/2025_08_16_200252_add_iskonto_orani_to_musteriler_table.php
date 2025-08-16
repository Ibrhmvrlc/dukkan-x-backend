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
        Schema::table('musteriler', function (Blueprint $table) {
            $table->decimal('iskonto_orani', 5, 2)->default(0)->after('musteri_tur_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('musteriler', function (Blueprint $table) {
            $table->dropColumn('iskonto_orani');
        });
    }
};
