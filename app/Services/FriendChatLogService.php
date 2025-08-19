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
                $table->text('answer');
                $table->timestamps();
            });
        }
        
        return $tableName;
    }
}