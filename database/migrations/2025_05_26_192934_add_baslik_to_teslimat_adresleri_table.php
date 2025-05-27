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
        Schema::table('teslimat_adresleri', function (Blueprint $table) {
            $table->string('baslik')->after('musteri_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teslimat_adresleri', function (Blueprint $table) {
            $table->dropColumn('baslik');
        });
    }
};
