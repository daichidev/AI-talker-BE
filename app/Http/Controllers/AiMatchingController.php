<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Profile;
use Carbon\Carbon;

class AiMatchingController extends Controller
{
    /**
     * 年齢・性別・地域でユーザーを検索
     */
    public function searchUsers(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'min_age' => 'nullable|integer|min:18|max:100',
            'max_age' => 'nullable|integer|min:18|max:100',
            'gender' => 'nullable|boolean',
            'hometown' => 'nullable|string|max:255',
        ]);

        $query = User::with(['profile']);

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
        if ($requestingUser && $requestingUser->ban_users) {
            $banUserIds = json_decode($requestingUser->ban_users, true);
            if (is_array($banUserIds) && !empty($banUserIds)) {
                $query->whereNotIn('id', $banUserIds);
            }
        }

        $users = $query->get()->map(function ($user) {
            $profile = $user->profile;
            $age = null;
            
            if ($profile && $profile->birthdate) {
                $age = Carbon::parse($profile->birthdate)->age;
            }

            return [
                'id' => $user->id,
                'name' => $profile->name,
            ];
        });

        return response()->json([
            'success' => true,
            'users' => $users,
            'count' => $users->count()
        ]);
    }

    public function selectUser(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'selected_user_id' => 'required|integer',
        ]);

        $requestingUser = User::find($request->user_id);
        if ($requestingUser) {
            $requestingUser->matching_users = $request->selected_user_id;
            $requestingUser->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'ユーザーを選択しました',
        ]);
    }
} 