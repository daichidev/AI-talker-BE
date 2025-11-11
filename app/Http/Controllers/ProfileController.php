<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Profile;
use App\Models\Syncro;
use App\Models\User;
use Illuminate\Support\Facades\Http;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\UserController;
use Carbon\Carbon;

class ProfileController extends Controller
{
    public function show($userId)
    {
        $profile = Profile::where('user_id', $userId)->first();
        $user = User::find($userId);

        if (!$profile) {
            return response()->json([
                'success' => false,
                'search_show_status' => $user->search_show_status,
                'data' => 'Profile not found'
            ]);
        }
        return response()->json([
            'success' => true,
            'data' => $profile,
            'search_show_status' => $user->search_show_status
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
            'description' => 'nullable|string',
            'comment' => 'nullable|string',
            'dialect' => 'nullable|string',
            'search_show_status' => 'nullable'
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

        $nonNullFields = array_filter($validated, function ($value) {
            return $value !== null;
        });
        $count = count($nonNullFields);

        $syncro = Syncro::where('user_id', $userId)->first();
        $syncro->score_profile = $count;
        $syncro->save();

        $user = User::find($userId);
        $user->search_show_status = $validated['search_show_status'];

        if (array_key_exists('address', $validated)) {
            $location = $this->geocodeNominatim($validated['address']);
            $user->location = $location;
        }
        $user->save();

        return response()->json([
            'success' => true,
            'location' => $location,
            'data' => $profile
        ]);
    }
    function geocodeNominatim(string $address): ?array
    {
        $response = Http::withHeaders([
            'User-Agent' => 'MYAI'
        ])->get('https://nominatim.openstreetmap.org/search', [
            'q' => $address,
            'format' => 'json',
            'limit' => 1
        ]);

        if ($response->failed() || empty($response[0])) {
            return null;
        }

        return [
            'lat' => (float) $response[0]['lat'],
            'lng' => (float) $response[0]['lon']
        ];
    }
}
