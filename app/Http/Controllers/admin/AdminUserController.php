<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class AdminUserController extends Controller
{
    public function showLogin()
    {
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (Auth::attempt($credentials)) {
            if (Auth::user()->role === 'admin') {
                return redirect()->route('admin.users.index');
            }
            Auth::logout();
            return back()->with('error', '管理者権限が必要です。');
        }

        return back()->with('error', 'メールアドレスまたはパスワードが正しくありません。');
    }

    public function index(Request $request)
    {
        $query = User::query();
        
        if ($request->has('search')) {
            $query->where('email', 'like', '%' . $request->search . '%');
        }
        
        $users = $query->get();
        return view('admin.users.index', compact('users'));
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
        ];

        // Only validate password if it's provided
        if ($request->filled('password')) {
            $rules['password'] = ['required', 'confirmed', Password::defaults()];
        }

        $messages = [
            'name.required' => '名前は必須です。',
            'name.max' => '名前は255文字以内で入力してください。',
            'email.required' => 'メールアドレスは必須です。',
            'email.email' => '有効なメールアドレスを入力してください。',
            'email.unique' => 'このメールアドレスは既に使用されています。',
            'password.confirmed' => 'パスワードが一致しません。',
        ];

        $validated = $request->validate($rules, $messages);

        // Remove password from validated data if it's not provided
        if (!$request->filled('password')) {
            unset($validated['password']);
        } else {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('admin.users.index')
            ->with('success', 'ユーザー情報を更新しました。');
    }

    public function destroy(User $user)
    {
        if ($user->face_photo) {
            // Delete the face photo from storage
            Storage::disk('public')->delete($user->face_photo);
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'ユーザーを削除しました。');
    }

    public function logout()
    {
        Auth::logout();
        return redirect()->route('admin.login');
    }
}   