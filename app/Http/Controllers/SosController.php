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
        $user = Profile::where('user_id', $request->user_id)->first();
        $receiverEmail = $user->sos_recipient;

        $messageText   = $request->input('message');
        $lat           = $request->input('latitude');
        $lng           = $request->input('longitude');
        $requesterName = $user->name;
        if (empty($receiverEmail)) {
            return response()->json(['ok' => false, 'error' => 'receiver email is empty'], 422);
        }

        Log::info('SOS: start', $request->all());

        try {
            Mail::send('emails.sos', [
                'requester_name' => $requesterName,
                'messageText'   => $messageText,
                'latitude'  => $lat,
                'longitude' => $lng,
                'sent_at'   => now()->format('Y/m/d H:i'),
            ], function ($mail) use ($receiverEmail) {
                $mail->to($receiverEmail)
                    ->subject('【緊急】SOS通知');
            });
            Log::info('SOS: mail sent (or attempted)');
            return response()->json(['status' => 'ok']);
        } catch (\Throwable $e) {
            Log::error('SOS: mail failed', ['error' => $e->getMessage()]);
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
