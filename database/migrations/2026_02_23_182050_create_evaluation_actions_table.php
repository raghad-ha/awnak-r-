<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('evaluation_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('volunteer_user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('action', ['warn','suspend','block']);
            $table->text('reason');
            $table->foreignId('decided_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_actions');
    }
};
