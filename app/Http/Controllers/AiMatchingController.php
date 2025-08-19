<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Profile;
use Carbon\Carbon;
use App\Services\FriendChatLogService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; 

class AiMatchingController extends Controller
{
    /**
     * 年齢・性別・地域でユーザーを検索
     */
    public function getUsers(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'min_age' => 'nullable|integer|min:18|max:100',
            'max_age' => 'nullable|integer|min:18|max:100',
            'gender' => 'nullable|boolean',
            'hometown' => 'nullable|string|max:255',
        ]);

        $query = User::with(['profile', 'avatars']);

        // 年齢フィルター
        if ($request->filled('min_age') || $request->filled('max_age')) {
            $query->whereHas('profile', function ($profileQuery) use ($request) {
                if ($request->filled('min_age')) {
                    $maxDate = Carbon::now()->subYears($request->min_age);
                    $profileQuery->where('birthdate', '<=', $maxDate);
                }
                if ($request->filled('max_age')) {
                    $minDate = Carbon::now()->subYears($request->max_age + 1);
                    $profileQuery->where('birthdate', '>', $minDate);
                }
            });
        }

        // 性別フィルター
        if ($request->filled('gender')) {
            $query->whereHas('profile', function ($profileQuery) use ($request) {
                $profileQuery->where('gender', $request->gender);
            });
        }

        // 地域フィルター
        if ($request->filled('hometown')) {
            $query->whereHas('profile', function ($profileQuery) use ($request) {
                $profileQuery->where('hometown', 'LIKE', '%' . $request->hometown . '%');
            });
        }

        // プロフィールが存在するユーザーのみを取得
        $query->whereHas('profile');

        // 自分自身を除外
        $query->where('id', '!=', $request->user_id);

        // ban_usersに含まれるユーザーを除外
        $requestingUser = User::find($request->user_id);

        if ($requestingUser && $requestingUser->friend_users) {
            $friendUserIds = json_decode($requestingUser->friend_users, true);
            if (is_array($friendUserIds) && !empty($friendUserIds)) {
                $query->whereIn('id', $friendUserIds);
            }
        }

        $users = $query->get()->map(function ($user) {
            $profile = $user->profile;
            $avatar = $user->avatars->first();

            return [
                'id' => $user->id,
                'name' => $profile['name'] ?? '',
                'subname' => $profile['bot_nickname'] ?? '',
                'avatar' => $avatar?->avatar_link,
            ];
        });

        return response()->json([
            'users' => $users,
        ]);
    }

    public function deleteFriend(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'delete_friend_id' => 'required|integer',
        ]);

        $user = User::find($request->user_id);
        $user->friend_users = json_encode(array_diff(json_decode($user->friend_users, true), [$request->delete_friend_id]));
        $user->save();

        return response()->json([
            'success' => true
        ]);
    }

    public function selectFriend(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'friend_id' => 'required|integer',
        ]);
        
        $friendUser = User::with(['profile', 'avatars'])->find($request->friend_id);

        return response()->json([
            'friend_user_avatar_link' => $friendUser->avatars->first()->avatar_link,
            'friend_user_name' => $friendUser->profile->name,
            'messages' => $this->getChatLogs($request->user_id, $request->friend_id),
        ]);
    }

    private function getChatLogs($userId, $friendId)
    {
        $tableName = app(FriendChatLogService::class)->getTableName($userId, $friendId);
    
        if (!Schema::hasTable($tableName)) {
            return collect();
        }

        $chatLogs = DB::table($tableName)
                    ->orderBy('created_at', 'desc')
                    ->get();

        return $chatLogs->flatMap(function ($chatLog) {
            return [
                ['text' => $chatLog->question, 'sender' => 'user'],
                ['text' => $chatLog->answer, 'sender' => 'bot'],
            ];
        });
    }
}