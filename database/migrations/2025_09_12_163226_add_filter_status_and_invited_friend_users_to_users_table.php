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
        Schema::table('users', function (Blueprint $table) {
            $table->json('filter_status')->default(json_encode([
                'name' => true, 
                'bot_nickname' => false,
                'gender' => true,
                'birthdate' => true,
                'hometown' => false,
                'address' => true,
                'blood_type' => false,
                'school_name' => false,
                'school_year' => false,
                'club_activity' => false,
                'department' => false,
                'job' => false,
                'company_name' => false,
                'position' => false,
                'hobby' => false,
                'family_structure' => false,
                'special_skills' => false,
                'dream' => false,
                'animal_fortune_telling_result' => false,
                'description' => false
            ]))->after('point');
            $table->longText('invited_friend_users')->nullable()->after('filter_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('filter_status');
            $table->dropColumn('invited_friend_users');
        });
    }
};
