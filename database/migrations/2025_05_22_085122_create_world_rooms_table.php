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
        Schema::create('world_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_big_category_id')->constrained('world_big_categories')->onDelete('cascade');
            $table->foreignId('world_medium_category_id')->constrained('world_medium_categories')->onDelete('cascade');
            $table->foreignId('world_small_category_id')->nullable()->constrained('world_small_categories');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('world_rooms');
    }
};
