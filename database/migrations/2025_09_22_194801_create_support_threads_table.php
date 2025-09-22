<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('support_threads', function (Blueprint $table) {
      $table->id();
      $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
      $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
      $table->enum('status', ['open','pending','resolved','closed'])->default('open')->index();
      $table->enum('priority', ['low','normal','high'])->default('normal')->index();
      $table->timestamp('last_message_at')->nullable()->index();
      $table->timestamps();
    });
  }
  public function down(): void {
    Schema::dropIfExists('support_threads');
  }
};