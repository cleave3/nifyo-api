<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;

class WelcomeEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $firstName;
    public $email;
    public $phone;
    public $password;
    public $link;

    /**
     * Create a new message instance.
     */
    public function __construct($firstName, $email, $phone, $password, $link)
    {
        $this->firstName = $firstName;
        $this->email = $email;
        $this->phone = $phone;
        $this->password = $password;
        $this->link = $link;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(env('MAIL_SENDER_EMAIL'), env('MAIL_SENDER_NAME')),
            subject: 'Welcome to the Team, ' . $this->firstName . '!',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.welcome',
            with: [
                'first_name' => $this->firstName,
                'email' => $this->email,
                'phone' => $this->phone,
                'password' => $this->password,
                'link' => $this->link,
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
