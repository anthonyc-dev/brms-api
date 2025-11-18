<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\RequestDocument;

class DocumentRequestCreated extends Notification
{
    use Queueable;

    protected $documentRequest;

    /**
     * Create a new notification instance.
     */
    public function __construct(RequestDocument $documentRequest)
    {
        $this->documentRequest = $documentRequest;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Document Request Confirmation - ' . $this->documentRequest->reference_number)
            ->greeting('Hello ' . $this->documentRequest->full_name . '!')
            ->line('Your document request has been successfully submitted.')
            ->line('**Request Details:**')
            ->line('Document Type: ' . $this->documentRequest->document_type)
            ->line('Reference Number: ' . $this->documentRequest->reference_number)
            ->line('Status: ' . ucfirst($this->documentRequest->status))
            ->line('Purpose: ' . $this->documentRequest->purpose)
            ->action('View Request', url('/requests/' . $this->documentRequest->id))
            ->line('We will process your request and notify you once it is ready.')
            ->line('Thank you for using our document request system!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'document_request_id' => $this->documentRequest->id,
            'reference_number' => $this->documentRequest->reference_number,
            'document_type' => $this->documentRequest->document_type,
            'status' => $this->documentRequest->status,
        ];
    }
}
