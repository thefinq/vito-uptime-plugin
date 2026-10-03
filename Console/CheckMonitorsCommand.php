<?php

namespace App\Vito\Plugins\Thefinq\VitoUptimePlugin\Console;

use App\Vito\Plugins\Thefinq\VitoUptimePlugin\Models\Monitor;
use App\Vito\Plugins\Thefinq\VitoUptimePlugin\Services\Checker;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Sleep;
use Throwable;

class CheckMonitorsCommand extends Command
{
    protected $signature = 'uptime:check
        {--once : Run a single pass over the due monitors and exit}
        {--budget=55 : Seconds this run may keep waiting for monitors that come due}';

    protected $description = 'Run the uptime monitors that are due';

    public function handle(Checker $checker): int
    {
        $deadline = microtime(true) + (int) $this->option('budget');
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

            if ($this->option('once') || ! $this->somethingDueBefore($deadline)) {
                break;
            }

            Sleep::for(1)->second();
        } while (microtime(true) < $deadline);

        $this->info("Checked $checked monitor(s).");

        return self::SUCCESS;
    }

    /**
     * The scheduler starts this command at the top of each minute; a monitor due at
     * 12:00:40 would otherwise wait for the next start. Keep running while any enabled
     * monitor comes due before the budget runs out.
     */
    private function somethingDueBefore(float $deadline): bool
    {
        $next = Monitor::enabled()->min('next_check_at');

        return $next !== null && Carbon::parse($next)->getTimestamp() <= (int) $deadline;
    }
}
