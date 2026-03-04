<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opportunity_id')->constrained('opportunities')->cascadeOnDelete();
            $table->foreignId('volunteer_user_id')->constrained('users')->cascadeOnDelete();

            $table->enum('status', ['pending','accepted','rejected','cancelled'])->default('pending');
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('message')->nullable();

            $table->timestamps();
            $table->unique(['opportunity_id','volunteer_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
