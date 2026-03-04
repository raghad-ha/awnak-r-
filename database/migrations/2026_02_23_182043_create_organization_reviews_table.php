<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('organization_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('volunteer_user_id')->constrained('users')->cascadeOnDelete();

            $table->unsignedTinyInteger('commitment');   // 1..5
            $table->unsignedTinyInteger('task_clarity'); // 1..5
            $table->unsignedTinyInteger('work_env');     // 1..5
            $table->unsignedTinyInteger('time_respect'); // 1..5

            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_reviews');
    }
};
