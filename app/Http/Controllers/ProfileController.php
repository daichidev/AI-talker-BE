<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Profile;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function show($userId)
    {
        $profile = Profile::where('user_id', $userId)->first();

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Profile not found'
            ]);
        }

        return response()->json($profile);
    }

    public function update(Request $request, $userId)
    {
        $validated = $request->validate([
            'name' => 'nullable|string',
            'ai_name' => 'nullable|string',
            'gender' => 'nullable|string',
            'birthdate' => 'nullable|date',
            'hometown' => 'nullable|string',
            'address' => 'nullable|string',
            'blood_type' => 'nullable|string',
            'school_name' => 'nullable|string',
            'company_name' => 'nullable|string',
            'income_or_allowance' => 'nullable|string',
            'hobby' => 'nullable|string',
            'family_structure' => 'nullable|string',
            'special_skills' => 'nullable|string',  
            'dream' => 'nullable|string',
            'favorite_type' => 'nullable|string',
            'weakness' => 'nullable|string',
            'animal_fortune_telling_result' => 'nullable|string',
        ]);

        $profile = Profile::updateOrCreate(
            ['user_id' => $userId],
            $validated
        );

        return response()->json($profile);
    }
}