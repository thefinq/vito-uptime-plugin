<?php

namespace App\Vito\Plugins\Thefinq\VitoUptimePlugin\Notifications;

use App\Notifications\AbstractNotification;
use App\Vito\Plugins\Thefinq\VitoUptimePlugin\Models\Monitor;
use Carbon\CarbonInterval;
use Illuminate\Notifications\Messages\MailMessage;

class MonitorUp extends AbstractNotification
{
    public function __construct(protected Monitor $monitor, protected int $downForSeconds) {}

    public function rawText(): string
    {
        return __('🟢 UP: :name (:url) — back after :duration', [
            'name' => $this->monitor->name,
            'url' => $this->monitor->url,
            'duration' => $this->duration(),
        ]);
    }

    public function toEmail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->success()
            ->subject(__('Monitor up: :name', ['name' => $this->monitor->name]))
            ->line(__(':url is reachable again after :duration.', ['url' => $this->monitor->url, 'duration' => $this->duration()]))
            ->action(__('Open monitor'), url('/uptime/'.$this->monitor->id));
    }

    private function duration(): string
    {
        return CarbonInterval::seconds(max(1, $this->downForSeconds))->cascade()->forHumans(['short' => true]);
    }
}
