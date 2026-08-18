<?php

namespace App\Notifications;

use App\Models\TenderResponse;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TenderAwardedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly TenderResponse $response) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tender = $this->response->tender;

        return (new MailMessage)
            ->subject('Tender award: '.$tender->tender_number)
            ->greeting('Congratulations '.$notifiable->name.',')
            ->line('Your bid has been selected as the winning response for '.$tender->title.'.')
            ->line('Tender: '.$tender->tender_number)
            ->line('Bid receipt: '.($this->response->receipt_number ?? 'Not recorded'))
            ->line('Awarded amount: TZS '.number_format((float) $this->response->total_amount, 2))
            ->action('View your responses', url('/tender-responses'))
            ->line('The procurement team will contact you with the next purchasing steps.');
    }
}
