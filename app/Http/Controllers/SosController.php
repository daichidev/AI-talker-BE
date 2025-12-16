<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\SOSSentMail;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

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
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'video_url' => 'required|url',
            'message' => 'required|string',
            'location' => 'required|json',
        ]);

        $user = Profile::find($request->user_id);
        $receiverEmail = $user->sos_recipient;

        $receiverEmail = $request->input('email');
        $messageText   = $request->input('message');
        $senderName    = config('app.name');

        Mail::send('emails.sos', [
            'user_name' => $senderName,
            'message'   => $messageText,
            'latitude'  => $lat,
            'longitude' => $lng,
            'sent_at'   => now()->format('Y/m/d H:i'),
        ], function ($mail) use ($receiverEmail) {
            $mail->to($receiverEmail)
                 ->subject('【緊急】SOS通知');
        });

        return response()->json(['status' => 'ok', 'text' => $messageText, 'name' => $senderName]);
    }
}
