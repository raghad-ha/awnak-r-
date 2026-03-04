<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('volunteer_skill', function (Blueprint $table) {
            $table->foreignId('volunteer_profile_id')->constrained('volunteer_profiles')->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained('skills')->cascadeOnDelete();
            $table->primary(['volunteer_profile_id','skill_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('volunteer_skill');
    }
};
