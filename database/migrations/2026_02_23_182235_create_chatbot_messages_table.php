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
    Schema::create('chatbot_messages', function (Blueprint $table) {
    $table->id();
    $table->foreignId('session_id')->constrained('chatbot_sessions')->cascadeOnDelete();
    $table->enum('sender', ['user','bot']);
    $table->text('body');
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chatbot_messages');
    }
};
