<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('join_request_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('join_request_id')->constrained('join_requests')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('file_type');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('join_request_files');
    }
};
