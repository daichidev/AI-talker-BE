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
        Schema::create('syncros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->integer('score_profile')->default(0);
            $table->boolean('done_animal_fortune')->default(false);
            $table->boolean('done_big5_analysis')->default(false);
            $table->boolean('done_kakeai')->default(false);
            $table->integer('score_login')->default(0);
            $table->integer('score_ai_talk')->default(0);
            $table->integer('score_friend_invite_sent')->default(0);
            $table->integer('score_friend_invite_received')->default(0);
            $table->boolean('done_personality_test')->default(false);
            $table->integer('score_account_link')->default(0);
            $table->integer('score_sns_link')->default(0);
            $table->boolean('done_location_info')->default(false);
            $table->boolean('done_cookie_on')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('syncros');
    }
};