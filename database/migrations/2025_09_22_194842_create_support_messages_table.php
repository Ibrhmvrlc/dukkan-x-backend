<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('support_messages', function (Blueprint $table) {
      $table->id();
      $table->foreignId('thread_id')->constrained('support_threads')->cascadeOnDelete();
      $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
      $table->text('body');
      $table->enum('importance', ['info','warning','critical'])->default('info')->index();
      $table->timestamp('read_at')->nullable()->index();
      $table->timestamps();
    });
  }
  public function down(): void {
    Schema::dropIfExists('support_messages');
  }
};