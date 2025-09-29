<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update filter_status for all existing users with new default values
        DB::table('users')
            ->whereNotNull('filter_status')
            ->update([
                'filter_status' => json_encode([
                    'name' => false, 
                    'bot_nickname' => true,
                    'gender' => true,
                    'birthdate' => false,
                    'hometown' => true,
                    'address' => false,
                    'blood_type' => true,
                    'school_name' => true,
                    'school_year' => true,
                    'club_activity' => true,
                    'department' => true,
                    'job' => true,
                    'company_name' => true,
                    'position' => true,
                    'hobby' => true,
                    'family_structure' => true,
                    'special_skills' => true,
                    'dream' => true,
                    'animal_fortune_telling_result' => true,
                    'description' => true
                ])
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to previous filter_status values
        DB::table('users')
            ->whereNotNull('filter_status')
            ->update([
                'filter_status' => json_encode([
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
                ])
            ]);
    }
};
