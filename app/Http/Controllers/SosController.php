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

        Mail::to($receiverEmail)->send(
            new SOSSentMail($messageText, $senderName)
        );

        return response()->json(['status' => 'ok']);
    }
}
