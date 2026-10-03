<?php

namespace App\Vito\Plugins\Thefinq\VitoUptimePlugin\Notifications;

use App\Notifications\AbstractNotification;
use Illuminate\Notifications\Messages\MailMessage;

class WebhookEvent extends AbstractNotification
{
    public function __construct(protected string $sourceName, protected string $level, protected string $text) {}

    public function rawText(): string
    {
        return $this->text.' ['.$this->sourceName.']';
    }

    public function toEmail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject(__(':source: :level', ['source' => $this->sourceName, 'level' => strtoupper($this->level)]))
            ->line($this->text);

        return $this->level === 'down' ? $mail->error() : $mail;
    }
}
