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
