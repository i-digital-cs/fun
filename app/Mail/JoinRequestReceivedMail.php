<?php

namespace App\Mail;

use App\Models\Event;
use App\Models\EventJoinRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class JoinRequestReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $organizer,
        public readonly User $requester,
        public readonly Event $event,
        public readonly EventJoinRequest $joinRequest,
        public readonly string $siteName,
        public readonly ?string $logoUrl,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('events.mail_join_request_subject', [
                'app' => $this->siteName,
                'event' => $this->event->title,
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.join-request-received',
            with: [
                'organizerName' => $this->organizer->name,
                'requesterName' => $this->requester->name,
                'eventTitle' => $this->event->title,
                'message' => $this->joinRequest->message,
                'siteName' => $this->siteName,
                'logoUrl' => $this->logoUrl,
                'reviewUrl' => event_route('events.requests', $this->event),
                'eventUrl' => $this->event->showUrl(),
            ],
        );
    }
}
