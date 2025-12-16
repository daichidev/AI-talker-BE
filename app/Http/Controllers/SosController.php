<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\SOSSentMail;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class SosController extends Controller
{
    public function uploadVideo(Request $request)
    {
        $request->validate([
            'video' => 'required|file|mimetypes:video/mp4,video/quicktime|max:51200', // max 50MB など
        ]);

        $path = $request->file('video')->store('sos_videos', 'public');

        return response()->json([
            'url' => $path,
        ]);
    }
    public function sendEmail(Request $request)
    {
        Log::info('SOS: start', $request->all());
    
        $validated = $request->validate([
            'user_id'   => 'required|exists:users,id',
            'video_url' => 'required|url',
            'message'   => 'required|string',
            'location'  => 'required|json',
        ]);
    
        $profile = Profile::findOrFail($validated['user_id']);
    
        // DBの宛先を使う（おすすめ）
        $receiverEmail = $profile->sos_recipient;
    
        // location から lat/lng を取り出す
        $location = json_decode($validated['location'], true);
        $lat = $location['latitude'] ?? null;
        $lng = $location['longitude'] ?? null;
    
        if (!$receiverEmail) {
            return response()->json(['ok' => false, 'error' => 'sos_recipient is empty'], 422);
        }
    
        try {
            Mail::send('emails.sos', [
                'user_name' => config('app.name'),
                'message'   => $validated['message'],
                'latitude'  => $lat,
                'longitude' => $lng,
                'sent_at'   => now()->format('Y/m/d H:i'),
            ], function ($mail) use ($receiverEmail) {
                $mail->to($receiverEmail)->subject('【緊急】SOS通知');
            });
    
            Log::info('SOS: mail sent (or attempted)');
    
            return response()->json(['ok' => true], 200);
    
        } catch (\Throwable $e) {
            Log::error('SOS: mail failed', ['error' => $e->getMessage()]);
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
