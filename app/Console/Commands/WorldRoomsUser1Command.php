<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WorldRoom;

class WorldRoomsUser1Command extends Command
{
    /**
     * コンソールコマンドの名前とシグネチャ
     *
     * @var string
     */
    protected $signature = 'world-rooms-user1 {user_id? : The ID of the user}';

    /**
     * コンソールコマンドの説明
     *
     * @var string
     */
    protected $description = 'Process world rooms for a specific user';

    /**
     * コンソールコマンドの実行
     */
    public function handle()
    {
        $userId = $this->argument('user_id') ?? 1;
        
        // ワールドルームの処理
        $worldRoom = WorldRoom::where('user_id', $userId)->first();

        if ($worldRoom) {
            if($worldRoom->world_small_category_id) {
                $worldRooms = WorldRoom::where('world_small_category_id', $worldRoom->world_small_category_id)->get();

                $userIds = $worldRooms->pluck('user_id')->toArray();

                $userIds = array_filter($userIds, function($id) use ($userId) {
                    return $id != $userId;
                });

                $randomUserId = !empty($userIds) ? $userIds[array_rand($userIds)] : null;

                \Log::info("Random User ID: {$randomUserId}");
            } else {
                $worldRooms = WorldRoom::where('world_medium_category_id', $worldRoom->world_medium_category_id)->get();
            }
        } else {
            $this->error("No world room found for user ID: {$userId}");
        }
        
        $this->info('Command executed successfully!');
    }
}