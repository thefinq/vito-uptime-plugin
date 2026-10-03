<?php

namespace App\Vito\Plugins\Thefinq\VitoUptimePlugin\Services;

use App\Facades\Notifier;
use App\Vito\Plugins\Thefinq\VitoUptimePlugin\Models\Monitor;
use App\Vito\Plugins\Thefinq\VitoUptimePlugin\Models\MonitorEvent;
use App\Vito\Plugins\Thefinq\VitoUptimePlugin\Notifications\MonitorDown;
use App\Vito\Plugins\Thefinq\VitoUptimePlugin\Notifications\MonitorUp;
use App\Vito\Plugins\Thefinq\VitoUptimePlugin\Plugin;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class Checker
{
    public const string USER_AGENT = 'VitoUptimePlugin/'.Plugin::VERSION;

    /**
     * Runs one check and records the outcome, including state changes and alerts.
     */
    public function run(Monitor $monitor): CheckResult
    {
        $result = $this->probe($monitor);
        $this->record($monitor, $result);

        return $result;
    }

    public function probe(Monitor $monitor): CheckResult
    {
        $started = hrtime(true);

        try {
            $response = Http::withUserAgent(self::USER_AGENT)
                ->timeout($monitor->timeout)
                ->connectTimeout(min(5, $monitor->timeout))
                ->withOptions(['allow_redirects' => ['max' => 5]])
                ->send(strtoupper($monitor->method), $monitor->url);
        } catch (Throwable $e) {
            return CheckResult::failure(Str::limit($e->getMessage(), 300), null, $this->elapsedMs($started));
        }

        $ms = $this->elapsedMs($started);
        $code = $response->status();

        if (! ExpectedStatus::matches($monitor->expected_status, $code)) {
            return CheckResult::failure("HTTP $code, expected {$monitor->expected_status}", $code, $ms);
        }

        if ($monitor->keyword !== null && $monitor->keyword !== '' && ! Str::contains($response->body(), $monitor->keyword, true)) {
            return CheckResult::failure('Keyword "'.$monitor->keyword.'" not found in the response', $code, $ms);
        }

        return CheckResult::success($code, $ms);
    }

    public function record(Monitor $monitor, CheckResult $result): void
    {
        $now = now();

        $monitor->last_checked_at = $now;
        $monitor->next_check_at = $monitor->nextCheckAfter($now);
        $monitor->last_status_code = $result->statusCode;
        $monitor->last_response_ms = $result->responseMs;
        $monitor->last_error = $result->error;

        if ($result->ok) {
            $monitor->consecutive_failures = 0;

            if (! $monitor->isUp()) {
                $wasDown = $monitor->isDown();
                $downFor = $wasDown && $monitor->last_changed_at ? $monitor->last_changed_at->diffInSeconds($now) : null;

                $monitor->state = Monitor::STATE_UP;
                $monitor->last_changed_at = $now;
                $monitor->save();

                if ($wasDown) {
                    $monitor->events()->create([
                        'type' => MonitorEvent::TYPE_UP,
                        'message' => 'HTTP '.$result->statusCode.' in '.$result->responseMs.' ms',
                        'duration_seconds' => $downFor,
                        'created_at' => $now,
                    ]);
                    Notifier::send($monitor, new MonitorUp($monitor, (int) $downFor));
                }

                return;
            }

            $monitor->save();

            return;
        }

        $monitor->consecutive_failures++;

        if (! $monitor->isDown() && $monitor->consecutive_failures >= max(1, $monitor->retries)) {
            $monitor->state = Monitor::STATE_DOWN;
            $monitor->last_changed_at = $now;
            $monitor->save();

            $monitor->events()->create([
                'type' => MonitorEvent::TYPE_DOWN,
                'message' => $result->error,
                'created_at' => $now,
            ]);
            Notifier::send($monitor, new MonitorDown($monitor, (string) $result->error));

            return;
        }

        $monitor->save();
    }

    private function elapsedMs(int|float $startedNs): int
    {
        return (int) round((hrtime(true) - $startedNs) / 1_000_000);
    }
}
