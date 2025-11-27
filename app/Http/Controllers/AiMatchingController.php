<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;
use App\Models\Profile;
use App\Models\Syncro;

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
            'name' => 'nullable|string',
            'min_age' => 'nullable|integer',
            'max_age' => 'nullable|integer',
            'gender' => 'nullable|boolean',
            'hometown' => 'nullable|string|max:255',
            'is_all_users' => 'nullable|boolean',
            'is_invited_friend_users' => 'nullable|boolean',
        ]);

        $query = User::with(['profile', 'avatars']);

        if ($request->filled('name')) {
            $query->whereHas('profile', function ($profileQuery) use ($request) {
                $profileQuery->where(function ($q) use ($request) {
                    $q->where('name', 'LIKE', '%' . $request->name . '%')
                    ->orWhere('bot_nickname', 'LIKE', '%' . $request->name . '%');
                });
            });
        }

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
                $profileQuery->where('gender', $request->gender ? "女性" : "男性");
            });
        }

        // 地域フィルター
        if ($request->filled('address')) {
            $query->whereHas('profile', function ($profileQuery) use ($request) {
                $profileQuery->where('address', 'LIKE', '%' . $request->address . '%');
            });
        }

        // プロフィールが存在するユーザーのみを取得
        $query->whereHas('profile');

        // 自分自身を除外
        $query->where('id', '!=', $request->user_id)->where('search_show_status', true);

        if ($request->filled('is_invited_friend_users')) {
            $requestingUser = User::find($request->user_id);

            if ($requestingUser && $requestingUser->invited_friend_users) {
                $invitedFriendUserIds = json_decode($requestingUser->invited_friend_users, true);
                if (is_array($invitedFriendUserIds) && !empty($invitedFriendUserIds)) {
                    $query->whereIn('id', $invitedFriendUserIds);
                } else {
                    return response()->json([
                        'users' => [],
                    ]);
                }
            } else {
                return response()->json([
                    'users' => [],
                ]);
            }
        } else if (!$request->filled('is_all_users')) {
            $requestingUser = User::find($request->user_id);
            if ($requestingUser && $requestingUser->friend_users) {
                $friendUserIds = json_decode($requestingUser->friend_users, true);
                if (is_array($friendUserIds) && !empty($friendUserIds)) {
                    $query->whereIn('id', $friendUserIds);
                } 
            } else {
                return response()->json([
                    'users' => [],
                ]);
            }
        }
        $syncroController = app(SyncroController::class);

        $users = $query->get()->map(function ($user) use ($request, $syncroController) {
            $profile = $user->profile;
            $avatar = $user->avatars->first();
            $syncro = Syncro::where('user_id', $user->id)->first();
            $chatLogs = $this->getAllChatLogs($request->user_id, $user->id);

            // Calculate sync level using SyncroController methods
            $totalPoints = $syncroController->calculateTotalPoints($syncro);
            $syncLevel = $syncroController->calculateSyncLevel($totalPoints);

            // 時間表示のロジック
            $timeDisplay = null;
            if ($chatLogs->count() > 0) {
                $lastMessage = $chatLogs->first();
                if ($lastMessage && isset($lastMessage->created_at)) {
                    $createdAt = Carbon::parse($lastMessage->created_at);
                    $now = Carbon::now();
                    
                    if ($createdAt->isToday()) {
                        // 本日の場合：時間（17:30）
                        $timeDisplay = $createdAt->format('H:i');
                    } else {
                        // 本日以外の場合：日付（8月20日）
                        $timeDisplay = $createdAt->format('n月j日');
                    }
                }
            }

            return [
                'id' => $user->id,
                'name' => $profile['bot_nickname'] ?? '',
                'comment' => $profile['comment'] ?? '',
                'avatar' => $avatar?->avatar_link,
                'last_message' => $chatLogs->count() > 0 ? $chatLogs->first()->answer : null,
                'time' => $timeDisplay,
                'syncLevel' => $syncLevel,
            ];
        });

        $invitedFriendUser = User::find($request->user_id);

        return response()->json([
            'users' => $users,
            'friend_users' => $invitedFriendUser->friend_users,
            'invite_friend_users' => $invitedFriendUser?->invite_friend_users,
        ]);
    }

    public function deleteFriend(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'delete_friend_id' => 'required|integer',
        ]);

        $user = User::find($request->user_id);
        $friend_users = array_diff(json_decode($user->friend_users, true), [$request->delete_friend_id]);
        $friend_users = array_values($friend_users);
        $friend_users_string = json_encode($friend_users); 
        $user->friend_users = count($friend_users) > 0 ? $friend_users_string : null;
        $user->save();

        $tableName = app(FriendChatLogService::class)->getTableName($request->user_id, $request->delete_friend_id);
        if (Schema::hasTable($tableName)) {
            Schema::drop($tableName);
        }

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
            'friend_user_name' => $friendUser->profile->bot_nickname,
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
                    ->orderBy('created_at', 'asc')
                    ->get();

        return $chatLogs->flatMap(function ($chatLog) {
            return [
                ['text' => $chatLog->question, 'sender' => 'user', 'time' => $chatLog->created_at],
                ['text' => $chatLog->answer, 'sender' => 'bot', 'time' => $chatLog->created_at, 'isNsfw' => (bool) $chatLog->is_nsfw_content],
            ];
        });
    }

    private function getAllChatLogs($userId, $friendId)
    {
        $tableName = app(FriendChatLogService::class)->getTableName($userId, $friendId);
    
        if (!Schema::hasTable($tableName)) {
            return collect();
        }

        return DB::table($tableName)
                    ->orderBy('created_at', 'desc')
                    ->get();
    }

    public function inviteFriend(Request $request) {
        $request->validate([
            'user_id' => 'required|integer',
            'friend_id' => 'required|integer',
        ]);
        
        
        $user = User::find($request->user_id);
        $inviteFriendUsers = json_decode($user->invite_friend_users, true) ?? [];
        
        if (!in_array($request->friend_id, $inviteFriendUsers)) {
            array_push($inviteFriendUsers, $request->friend_id);
            $user->invite_friend_users = json_encode(array_values($inviteFriendUsers));
            $user->save();
        }

        $friendUser = User::find($request->friend_id);
        $invitedFriendUsers = json_decode($friendUser->invited_friend_users, true) ?? [];

        if (!in_array($request->user_id, $invitedFriendUsers)) {
            array_push($invitedFriendUsers, $request->user_id);
            $friendUser->invited_friend_users = json_encode(array_values($invitedFriendUsers));
            $friendUser->save();
        }

        return response()->json([
            'success' => true
        ]);
    }

    public function matchFriend(Request $request) {
        $request->validate([
            'user_id' => 'required|integer',
            'friend_id' => 'required|integer',
        ]);
        
        
        $user = User::find($request->user_id);

        $invitedFriendUsers = json_decode($user->invited_friend_users, true) ?? [];
        $friendUsers = json_decode($user->friend_users, true) ?? [];

        if (in_array($request->friend_id, $invitedFriendUsers)) {
            $invitedFriendUsers = array_filter($invitedFriendUsers, function($id) use ($request) {
                return $id !== $request->friend_id;
            });
            $user->invited_friend_users = json_encode(array_values($invitedFriendUsers));
            
            if (!in_array($request->friend_id, $friendUsers)) {
                $friendUsers[] = $request->friend_id;
                $user->friend_users = json_encode(array_values($friendUsers));
            }
            
            $user->save();
        }

        $friendUser = User::find($request->friend_id);

        $inviteFriendUsers = json_decode($friendUser->invite_friend_users, true) ?? [];
        $friendYourUsers = json_decode($friendUser->friend_users, true) ?? [];

        if (in_array($request->user_id, $inviteFriendUsers)) {

            $inviteFriendUsers = array_filter($inviteFriendUsers, function($id) use ($request) {
                return $id !== $request->user_id;
            });
            $friendUser->invite_friend_users = json_encode(array_values($inviteFriendUsers));
            
            if (!in_array($request->user_id, $friendYourUsers)) {
                $friendYourUsers[] = $request->user_id;
                $friendUser->friend_users = json_encode(array_values($friendYourUsers));
            }
            
            $friendUser->save();
        }

        return response()->json([
            'success' => true
        ]);
    }

    public function rejectFriend(Request $request) {
        $request->validate([
            'user_id' => 'required|integer',
            'friend_id' => 'required|integer',
        ]);
        
        
        $user = User::find($request->user_id);

        $invitedFriendUsers = json_decode($user->invited_friend_users, true) ?? [];

        if (in_array($request->friend_id, $invitedFriendUsers)) {
            $invitedFriendUsers = array_filter($invitedFriendUsers, function($id) use ($request) {
                return $id !== $request->friend_id;
            });
            $user->invited_friend_users = json_encode(array_values($invitedFriendUsers));

            $user->save();
        }

        $friendUser = User::find($request->friend_id);

        $inviteFriendUsers = json_decode($friendUser->invite_friend_users, true) ?? [];

        if (in_array($request->user_id, $inviteFriendUsers)) {
            $inviteFriendUsers = array_filter($inviteFriendUsers, function($id) use ($request) {
                return $id !== $request->user_id;
            });
            $friendUser->invite_friend_users = json_encode(array_values($inviteFriendUsers));
            
            $friendUser->save();
        }

        return response()->json([
            'success' => true
        ]);
    }
}