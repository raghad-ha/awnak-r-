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
    {Schema::create('post_tags', function (Blueprint $table) {
    $table->id();
    $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();

    $table->morphs('taggable'); // taggable_type + taggable_id

    $table->foreignId('tagged_by_user_id')->constrained('users')->cascadeOnDelete();
    $table->timestamps();

    $table->unique(['post_id','taggable_type','taggable_id']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_tags');
    }
};
