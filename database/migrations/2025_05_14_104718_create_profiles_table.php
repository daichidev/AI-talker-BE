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
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->string('name')->nullable();
            $table->string('bot_nickname')->nullable();
            $table->string('gender', 10)->nullable();
            $table->date('birthdate')->nullable();
            $table->string('hometown')->nullable();
            $table->string('address')->nullable();
            $table->string('blood_type', 3)->nullable();
            $table->string('school_name')->nullable();
            $table->string('school_year')->nullable();
            $table->string('club_activity')->nullable();
            $table->string('department')->nullable();
            $table->string('job')->nullable();
            $table->string('company_name')->nullable();
            $table->string('position')->nullable();
            $table->text('hobby')->nullable();
            $table->text('family_structure')->nullable();
            $table->text('special_skills')->nullable();
            $table->text('dream')->nullable();
            $table->text('animal_fortune_telling_result')->nullable();
            $table->text('description')->nullable();
            $table->string('comment')->nullable();
            $table->string('dialect')->nullable();
            $table->string('sos_recipient')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
