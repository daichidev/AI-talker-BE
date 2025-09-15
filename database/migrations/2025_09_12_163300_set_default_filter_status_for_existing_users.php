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
        // Set default filter_status for existing users
        DB::table('users')
            ->whereNull('filter_status')
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Set filter_status to null for all users
        DB::table('users')->update(['filter_status' => null]);
    }
};
