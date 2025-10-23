<?php

namespace App\Services;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class FriendChatLogService
{
    public function getTableName($userId, $friendId)
    {
        return "chat_logs_" . $userId."_".$friendId;
    }

    public function ensureUserTableExists($userId, $friendId)
    {
        $tableName = $this->getTableName($userId, $friendId);
        
        if (!Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->text('question');
                $table->boolean('is_nsfw')->default(false);
                $table->boolean('is_nsfw_content')->default(false);
                $table->text('answer');
                $table->timestamps();
            });
        }
        
        return $tableName;
    }

    public function dropFriendTable($userId, $friendId)
    {
        $tableName = $this->getTableName($userId, $friendId);
        
        if (Schema::hasTable($tableName)) {
            Schema::drop($tableName);
            return true;
        }
        
        return false;
    }

    public function dropAllTablesForUser($userId)
    {
        // Get all users to find friend relationships
        $users = \App\Models\User::all();
        $droppedTables = [];
        
        foreach ($users as $user) {
            $friendIds = json_decode($user->friend_users, true) ?: [];
            
            // Check if this user has the target user as a friend
            if (in_array($userId, $friendIds)) {
                $tableName = $this->getTableName($user->id, $userId);
                if (Schema::hasTable($tableName)) {
                    Schema::drop($tableName);
                    $droppedTables[] = $tableName;
                }
            }
        }
        
        return $droppedTables;
    }
}