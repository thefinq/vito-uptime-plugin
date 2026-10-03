<?php

namespace App\Vito\Plugins\Thefinq\VitoUptimePlugin\Console;

use App\Vito\Plugins\Thefinq\VitoUptimePlugin\Models\Monitor;
use App\Vito\Plugins\Thefinq\VitoUptimePlugin\Services\Checker;
use App\Vito\Plugins\Thefinq\VitoUptimePlugin\Services\Interval;
use Illuminate\Console\Command;
use Illuminate\Support\Sleep;
use Throwable;

class CheckMonitorsCommand extends Command
{
    protected $signature = 'uptime:check
        {--once : Run a single pass over the due monitors and exit}
        {--budget=55 : Seconds to keep looping when sub-minute intervals exist}';

    protected $description = 'Run the uptime monitors that are due';

    public function handle(Checker $checker): int
    {
        $deadline = microtime(true) + (int) $this->option('budget');
        $loop = ! $this->option('once') && Interval::needsLoop(
            Monitor::enabled()->whereNull('cron')->pluck('interval_seconds')
        );
        $checked = 0;

        do {
            $due = Monitor::due()->orderBy('next_check_at')->get();

            foreach ($due as $monitor) {
                try {
                    $result = $checker->run($monitor);
                    $checked++;
                    $this->line(sprintf(
                        '%s %s %s%s',
                        $result->ok ? 'ok  ' : 'FAIL',
                        $monitor->name,
                        $result->statusCode ?? '-',
                        $result->error ? ' '.$result->error : ''
                    ));
                } catch (Throwable $e) {
                    report($e);
                    $this->error($monitor->name.': '.$e->getMessage());
                }
            }

            if (! $loop) {
                break;
            }

            Sleep::for(1)->second();
        } while (microtime(true) < $deadline);

        $this->info("Checked $checked monitor(s).");

        return self::SUCCESS;
    }
}
