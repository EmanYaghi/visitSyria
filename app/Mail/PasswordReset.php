<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordReset extends Mailable
{
    use Queueable, SerializesModels;
    public $user;
    public function __construct($user)
    {
        $this->user=$user;
    }
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Password Reset',
        );
    }
    public function content(): Content
    {
        return new Content(
            view: 'emails.PasswordReset',
        );
    }
    public function attachments(): array
    {
        return [];
    }
}
