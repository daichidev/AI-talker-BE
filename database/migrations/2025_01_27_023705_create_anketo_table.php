<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAnketoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('anketos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name')->nullable();
            $table->string('animal_fortune_telling')->nullable();
            $table->string('animal_fortune_telling_characteristics')->nullable();
            $table->string('birthdate')->nullable();
            $table->string('gender')->nullable();
            $table->string('user_nickname')->nullable();
            $table->string('bot_nickname')->nullable();
            $table->string('hometown')->nullable();
            $table->string('address')->nullable();
            $table->string('blood_type')->nullable();
            $table->string('job')->nullable();
            $table->string('hobby')->nullable();
            
            // Personality traits
            $table->integer('is_sociable')->nullable();
            $table->integer('likes_group_activities')->nullable();
            $table->integer('energetic_at_parties')->nullable();
            $table->integer('comfortable_public_speaking')->nullable();

            $table->integer('helpful_to_others')->nullable();
            $table->integer('respects_others_opinions')->nullable();
            $table->integer('avoids_conflicts')->nullable();
            $table->integer('tries_to_be_kind')->nullable();

            $table->integer('meets_deadlines')->nullable();
            $table->integer('plans_ahead')->nullable();
            $table->integer('rarely_forgets_things')->nullable();
            $table->integer('takes_responsibility')->nullable();

            $table->integer('easily_anxious')->nullable();
            $table->integer('tends_to_feel_down')->nullable();
            $table->integer('dwells_on_mistakes')->nullable();
            $table->integer('sensitive_to_pressure')->nullable();
            
            $table->integer('likes_new_experiences')->nullable();
            $table->integer('interested_in_arts')->nullable();
            $table->integer('open_to_new_ideas')->nullable();
            $table->integer('enjoys_change')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('anketos');
    }
}
