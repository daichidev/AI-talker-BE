<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SOSSentMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $messageText;
    public string $senderName;

    public function __construct($messageText, $senderName)
    {
        $this->messageText = $messageText;
        $this->senderName = $senderName;
    }

    public function build()
    {
        return $this
            ->subject('【SOS通知】緊急メッセージが届きました')
            ->markdown('emails.sos');
    }
}
