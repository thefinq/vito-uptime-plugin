<?php

namespace App\Vito\Plugins\Thefinq\VitoUptimePlugin\Services;

use Illuminate\Support\Str;

/**
 * Turns the JSON that Uptime Kuma's "Webhook" notification posts into one line of text.
 *
 * Kuma sends {"heartbeat": {...}, "monitor": {...}, "msg": "..."}; heartbeat.status is
 * 0 = down, 1 = up, 2 = pending, 3 = maintenance. A test notification from Kuma has no
 * heartbeat and no monitor, only "msg".
 */
class KumaPayload
{
    public const string DOWN = 'down';

    public const string UP = 'up';

    public const string PENDING = 'pending';

    public const string MAINTENANCE = 'maintenance';

    public const string INFO = 'info';

    /**
     * @param  array<string, mixed>  $payload
     * @return array{level: string, text: string}
     */
    public static function parse(array $payload): array
    {
        $heartbeat = is_array($payload['heartbeat'] ?? null) ? $payload['heartbeat'] : [];
        $monitor = is_array($payload['monitor'] ?? null) ? $payload['monitor'] : [];

        $level = match ((int) ($heartbeat['status'] ?? -1)) {
            0 => self::DOWN,
            1 => self::UP,
            2 => self::PENDING,
            3 => self::MAINTENANCE,
            default => self::INFO,
        };

        $name = trim((string) ($monitor['name'] ?? ''));
        $target = trim((string) ($monitor['url'] ?? ''));
        if ($target === '' && ! empty($monitor['hostname'])) {
            $target = $monitor['hostname'].(! empty($monitor['port']) ? ':'.$monitor['port'] : '');
        }
        $detail = trim((string) ($heartbeat['msg'] ?? ''));
        $fallback = trim((string) ($payload['msg'] ?? ''));

        if ($level === self::INFO || $name === '') {
            $text = $fallback !== '' ? $fallback : 'Event received without details';

            return ['level' => $level, 'text' => Str::limit($text, 500)];
        }

        $icon = match ($level) {
            self::DOWN => '🔴 DOWN',
            self::UP => '🟢 UP',
            self::PENDING => '🟡 PENDING',
            default => '🔧 MAINTENANCE',
        };

        $text = $icon.': '.$name.($target !== '' ? ' ('.$target.')' : '').($detail !== '' ? ' — '.$detail : '');

        return ['level' => $level, 'text' => Str::limit($text, 500)];
    }
}
