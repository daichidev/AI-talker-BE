<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PersonalityAssessment;

class PersonalityAssessmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = PersonalityAssessment::with('user');
    
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }
    
        if ($request->has('personality_type')) {
            $query->where('personality_type', $request->personality_type);
        }
    
        return $query->get();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'personality_type' => 'required|string|max:255',
            'result' => 'nullable|string',
        ]);
    
        // If user_id + personality_type already exist, update result. Otherwise, create new.
        $assessment = PersonalityAssessment::updateOrCreate(
            [
                'user_id' => $validated['user_id'],
                'personality_type' => $validated['personality_type']
            ],
            [
                'result' => $validated['result']
            ]
        );
    
        return response()->json($assessment, 201);
    }
    

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
