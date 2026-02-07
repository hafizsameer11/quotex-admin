<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $otpCode;
    public $type;
    public $expiresIn;

    /**
     * Create a new message instance.
     */
    public function __construct(string $otpCode, string $type, int $expiresIn = 10)
    {
        $this->otpCode = $otpCode;
        $this->type = $type;
        $this->expiresIn = $expiresIn;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = match($this->type) {
            'signup' => 'Verify Your Email - ANH Registration',
            'login' => 'Login Verification Code - ANH',
            'withdrawal' => 'Withdrawal Verification Code - ANH',
            default => 'Verification Code - ANH'
        };

        $envelope = new Envelope(
            subject: $subject,
        );
        
        $envelope->from(
            address: config('mail.from.address'),
            name: config('mail.from.name', 'ANH Asset Holding Network')
        );
        
        return $envelope;
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.otp',
            with: [
                'otpCode' => $this->otpCode,
                'type' => $this->type,
                'expiresIn' => $this->expiresIn,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
