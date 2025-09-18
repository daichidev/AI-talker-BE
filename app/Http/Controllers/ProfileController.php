<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Profile;
use App\Models\Syncro;
use App\Models\User;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\UserController;
use Carbon\Carbon;

class ProfileController extends Controller
{
    public function show($userId)
    {
        $profile = Profile::where('user_id', $userId)->first();

        if (!$profile) {
            return response()->json([
                'success' => false,
                'data' => 'Profile not found'
            ]);
        }

        $user = User::find($userId);
        return response()->json([
            'success' => true,
            'data' => $profile,
            'filter_status' => $user->filter_status
        ]);
    }

    public function update(Request $request, $userId)
    {
        $validated = $request->validate([
            'name' => 'nullable|string',
            'bot_nickname' => 'nullable|string',
            'gender' => 'nullable|string',
            'birthdate' => 'nullable|date',
            'hometown' => 'nullable|string',
            'address' => 'nullable|string',
            'blood_type' => 'nullable|string',
            'school_name' => 'nullable|string',
            'school_year' => 'nullable|string',
            'club_activity' => 'nullable|string',
            'department' => 'nullable|string',
            'job' => 'nullable|string',
            'company_name' => 'nullable|string',
            'position' => 'nullable|string',
            'hobby' => 'nullable|string',
            'family_structure' => 'nullable|string',
            'special_skills' => 'nullable|string',
            'dream' => 'nullable|string',
            'animal_fortune_telling_result' => 'nullable|string',
            'description' => 'nullable|string'
        ]);

        // If birthdate is being updated, calculate the animal sign
        if (isset($validated['birthdate'])) {
            // Convert date from YYYY-MM-DD to YYYY.M.D format
            $formattedDate = Carbon::parse($validated['birthdate'])->format('Y.n.j');
            
            $userController = new UserController();
            $birthdate_data = $userController->getAnimalSign($formattedDate);
            $validated['animal_fortune_telling_result'] = $birthdate_data['animal_fortune_telling_result'];
        }

        $profile = Profile::updateOrCreate(
            ['user_id' => $userId],
            $validated
        );

        $nonNullFields = array_filter($validated, function($value) {
            return $value !== null;
        });
        $count = count($nonNullFields);

        $syncro = Syncro::where('user_id', $userId)->first();
        $syncro->score_profile = $count;
        $syncro->save();

        return response()->json([
            'success' => true,
            'data' => $profile
        ]);
    }
}