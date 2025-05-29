<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

use App\Models\WorldBigCategory;
use App\Models\WorldMediumCategory;
use App\Models\WorldSmallCategory;
use App\Models\WorldRoom;

class WorldController extends Controller
{
    public function getWorldBigCategory(Request $request)
    {
        $worldBigCategory = WorldBigCategory::all();
        return response()->json([
            'success' => true,
            'data' => $worldBigCategory
        ]);
    }

    public function getWorldMediumCategory($worldBigCategoryId)
    {
        $worldMediumCategory = WorldMediumCategory::where('world_big_category_id', $worldBigCategoryId)->get();
        return response()->json([
            'success' => true,
            'data' => $worldMediumCategory
        ]);
    }

    public function getWorldSmallCategory($worldMediumCategoryId)
    {
        $worldSmallCategory = WorldSmallCategory::where('world_medium_category_id', $worldMediumCategoryId)->get();
        return response()->json([
            'success' => true,
            'data' => $worldSmallCategory
        ]);
    }

    public function postWorldRoom(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required',
            'world_big_category_id' => 'required|exists:world_big_categories,id',
            'world_medium_category_id' => 'required|exists:world_medium_categories,id',
        ]);

        try {
            // コマンドファイルが存在するか確認し、存在しない場合は作成
            $commandName = 'WorldRoomsUser' . $validated['user_id'];
            $commandPath = app_path('Console/Commands/' . $commandName . 'Command.php');
            
            if (!file_exists($commandPath)) {
                // CommandGeneratorControllerを使用してコマンドを生成
                $commandRequest = new Request([
                    'name' => $commandName,
                    'path' => 'app/Console/Commands',
                    'user_id' => $validated['user_id']
                ]);
                
                app(CommandGeneratorController::class)->generateCommand($commandRequest);
            }

            // メインのworld_roomsレコードを更新または作成
            $data = [
                'user_id' => $validated['user_id'],
                'world_big_category_id' => $validated['world_big_category_id'],
                'world_medium_category_id' => $validated['world_medium_category_id'],
                'world_small_category_id' => $request['world_small_category_id'] ?? NULL
            ];
            
            $worldRoom = WorldRoom::updateOrCreate(
                ['user_id' => $validated['user_id']],
                $data
            );
        
            return response()->json([
                'success' => true,
                'message' => $worldRoom->wasRecentlyCreated ? 'ワールドルームが正常に作成されました' : 'ワールドルームが正常に更新されました',
                'data' => $worldRoom
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ワールドルームの作成/更新に失敗しました: ' . $e->getMessage()
            ], 500);
        }
    }
}