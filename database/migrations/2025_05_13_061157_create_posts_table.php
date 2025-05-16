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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('meal_name');
            $table->string('have_it');
            $table->string('food_type');
            $table->string('location');
            $table->text('description');
            $table->string('rating');
            $table->json('photo');
            $table->json('tagged')->nullable();
            $table->unsignedBigInteger('tagged_count')->default(0);
            $table->enum('status',['pending','approved','canceled'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
