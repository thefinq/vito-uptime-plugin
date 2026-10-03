<?php

namespace App\Vito\Plugins\Thefinq\VitoUptimePlugin\Services;

use App\Vito\Plugins\Thefinq\VitoUptimePlugin\Models\KumaConnection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Reads monitor state from an Uptime Kuma instance. Kuma has no REST API for monitors;
 * what it does offer is a Prometheus /metrics endpoint protected by an API key
 * (Settings → API Keys). That is enough for a read-only view.
 */
class KumaClient
{
    /**
     * @return array<int, array{name: string, type: string, target: string, status: string, response_ms: ?int, cert_days: ?int, cert_valid: ?bool}>
     *
     * @throws KumaException
     */
    public function monitors(KumaConnection $connection): array
    {
        try {
            $response = Http::withUserAgent(Checker::USER_AGENT)
                ->withBasicAuth('', $connection->api_key)
                ->withOptions(['verify' => $connection->verify_tls])
                ->timeout(15)
                ->get($connection->metricsUrl());
        } catch (Throwable $e) {
            throw new KumaException(Str::limit($e->getMessage(), 300));
        }

        if ($response->status() === 401) {
            throw new KumaException('Uptime Kuma rejected the API key (401).');
        }

        if (! $response->successful()) {
            throw new KumaException('Uptime Kuma answered HTTP '.$response->status().' for /metrics.');
        }

        if (! str_contains($response->body(), 'monitor_status')) {
            throw new KumaException('No monitor metrics in the response. Is this the base URL of Uptime Kuma (1.21 or newer)?');
        }

        return self::parse($response->body());
    }

    /**
     * @return array<int, array{name: string, type: string, target: string, status: string, response_ms: ?int, cert_days: ?int, cert_valid: ?bool}>
     */
    public static function parse(string $metrics): array
    {
        $monitors = [];

        foreach (preg_split('/\r?\n/', $metrics) ?: [] as $line) {
            if (! preg_match('/^(monitor_status|monitor_response_time|monitor_cert_days_remaining|monitor_cert_is_valid)\{(.*)\}\s+(-?[0-9.]+(?:e[+-]?\d+)?)\s*$/i', $line, $m)) {
                continue;
            }

            $labels = self::labels($m[2]);
            $name = $labels['monitor_name'] ?? '';
            if ($name === '') {
                continue;
            }

            $target = $labels['monitor_url'] ?? '';
            if ($target === '' || $target === 'null' || preg_match('#^https?://$#', $target)) {
                $host = $labels['monitor_hostname'] ?? '';
                $port = $labels['monitor_port'] ?? '';
                $target = ($host !== '' && $host !== 'null') ? $host.(($port !== '' && $port !== 'null') ? ':'.$port : '') : '';
            }

            $key = $name.'|'.$target;
            $monitors[$key] ??= [
                'name' => $name,
                'type' => $labels['monitor_type'] ?? '',
                'target' => $target,
                'status' => 'unknown',
                'response_ms' => null,
                'cert_days' => null,
                'cert_valid' => null,
            ];

            $value = (float) $m[3];
            match (strtolower($m[1])) {
                'monitor_status' => $monitors[$key]['status'] = match ((int) $value) {
                    1 => 'up',
                    0 => 'down',
                    2 => 'pending',
                    3 => 'maintenance',
                    default => 'unknown',
                },
                'monitor_response_time' => $monitors[$key]['response_ms'] = $value < 0 ? null : (int) round($value),
                'monitor_cert_days_remaining' => $monitors[$key]['cert_days'] = (int) round($value),
                'monitor_cert_is_valid' => $monitors[$key]['cert_valid'] = $value >= 1,
                default => null,
            };
        }

        $list = array_values($monitors);
        usort($list, fn (array $a, array $b): int => [self::order($a['status']), $a['name']] <=> [self::order($b['status']), $b['name']]);

        return $list;
    }

    /**
     * @return array<string, string>
     */
    private static function labels(string $raw): array
    {
        $labels = [];
        if (preg_match_all('/(\w+)="((?:[^"\\\\]|\\\\.)*)"/', $raw, $pairs, PREG_SET_ORDER)) {
            foreach ($pairs as $pair) {
                $labels[$pair[1]] = stripcslashes($pair[2]);
            }
        }

        return $labels;
    }

    private static function order(string $status): int
    {
        return match ($status) {
            'down' => 0,
            'pending' => 1,
            'maintenance' => 2,
            'up' => 3,
            default => 4,
        };
    }
}
