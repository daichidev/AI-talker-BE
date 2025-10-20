<?php

namespace App\Services;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class ChatLogService
{
    public function getTableName($userId)
    {
        return "chat_logs_" . $userId;
    }

    public function ensureUserTableExists($userId)
    {
        $tableName = $this->getTableName($userId);
        
        if (!Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->text('question');
                $table->text('answer');
                $table->boolean('is_nsfw')->default(false);
                $table->timestamps();
            });
        }
        
        return $tableName;
    }

    public function dropUserTable($userId)
    {
        $tableName = $this->getTableName($userId);
        
        if (Schema::hasTable($tableName)) {
            Schema::drop($tableName);
            return true;
        }
        
        return false;
    }

    public function getUserTableName($userId)
    {
        return $this->getTableName($userId);
    }

    public function tableExists($userId)
    {
        return Schema::hasTable($this->getTableName($userId));
    }
}