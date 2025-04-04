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
                $table->timestamps();
            });
        }
        
        return $tableName;
    }
}