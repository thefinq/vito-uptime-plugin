<?php

namespace App\Vito\Plugins\Thefinq\VitoUptimePlugin\Services;

use Carbon\Carbon;
use Cron\CronExpression;

/**
 * A monitor runs either every N seconds (presets below, anything from 10 s up) or on a
 * cron expression with minute granularity. Seconds-based intervals are served by the
 * minute-long check loop; cron expressions by the usual scheduler semantics.
 */
class Interval
{
    public const int MIN_SECONDS = 10;

    public const string CUSTOM = 'cron';

    /**
     * @return array<int|string, string> value => label, value is seconds (int key) or "cron"
     */
    public static function presets(): array
    {
        return [
            '10' => 'Every 10 seconds',
            '15' => 'Every 15 seconds',
            '30' => 'Every 30 seconds',
            '60' => 'Every minute',
            '120' => 'Every 2 minutes',
            '300' => 'Every 5 minutes',
            '600' => 'Every 10 minutes',
            '900' => 'Every 15 minutes',
            '1800' => 'Every 30 minutes',
            '3600' => 'Every hour',
            '21600' => 'Every 6 hours',
            '43200' => 'Every 12 hours',
            '86400' => 'Every day',
            self::CUSTOM => 'Cron expression',
        ];
    }

    public static function isValidCron(string $expression): bool
    {
        return CronExpression::isValidExpression(trim($expression));
    }

    public static function next(Carbon $from, ?int $seconds, ?string $cron): Carbon
    {
        if ($cron !== null && $cron !== '') {
            return Carbon::instance((new CronExpression(trim($cron)))->getNextRunDate($from->toDateTime()));
        }

        return $from->copy()->addSeconds(max(self::MIN_SECONDS, (int) $seconds));
    }

    public static function label(?int $seconds, ?string $cron): string
    {
        if ($cron !== null && $cron !== '') {
            return 'cron '.$cron;
        }

        $presets = self::presets();
        if (isset($presets[(string) $seconds])) {
            return $presets[(string) $seconds];
        }

        return 'Every '.$seconds.' seconds';
    }

    /**
     * Whether any enabled monitor needs sub-minute attention, i.e. the check command
     * must keep looping for the rest of the minute instead of exiting after one pass.
     */
    public static function needsLoop(iterable $intervals): bool
    {
        foreach ($intervals as $seconds) {
            if ($seconds !== null && (int) $seconds < 60) {
                return true;
            }
        }

        return false;
    }
}
