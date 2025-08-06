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
            $table->json('friend_users')->nullable()->after('match_user_id');
            $table->json('show_of_users')->nullable()->after('friend_users');
            $table->json('ban_users')->nullable()->after('show_of_users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('friend_users');
            $table->dropColumn('show_of_users');
            $table->dropColumn('ban_users');
        });
    }
};
