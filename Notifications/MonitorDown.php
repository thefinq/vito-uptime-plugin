<?php

namespace App\Vito\Plugins\Thefinq\VitoUptimePlugin\Notifications;

use App\Notifications\AbstractNotification;
use App\Vito\Plugins\Thefinq\VitoUptimePlugin\Models\Monitor;
use Illuminate\Notifications\Messages\MailMessage;

class MonitorDown extends AbstractNotification
{
    public function __construct(protected Monitor $monitor, protected string $reason) {}

    public function rawText(): string
    {
        return __('🔴 DOWN: :name (:url) — :reason', [
            'name' => $this->monitor->name,
            'url' => $this->monitor->url,
            'reason' => $this->reason,
        ]);
    }

    public function toEmail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject(__('Monitor down: :name', ['name' => $this->monitor->name]))
            ->line(__(':url is down.', ['url' => $this->monitor->url]))
            ->line($this->reason)
            ->action(__('Open monitor'), url('/uptime/'.$this->monitor->id));
    }
}
