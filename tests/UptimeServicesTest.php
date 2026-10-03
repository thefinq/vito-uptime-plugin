<?php

use App\Vito\Plugins\Thefinq\VitoUptimePlugin\Services\ExpectedStatus;
use App\Vito\Plugins\Thefinq\VitoUptimePlugin\Services\Interval;
use Carbon\Carbon;
use Tests\TestCase;

uses(TestCase::class);

test('expected status accepts codes, lists, ranges and classes', function () {
    expect(ExpectedStatus::isValid('200'))->toBeTrue()
        ->and(ExpectedStatus::isValid('200,204'))->toBeTrue()
        ->and(ExpectedStatus::isValid('200-299'))->toBeTrue()
        ->and(ExpectedStatus::isValid('2xx'))->toBeTrue()
        ->and(ExpectedStatus::isValid('2xx, 301'))->toBeTrue()
        ->and(ExpectedStatus::isValid(''))->toBeFalse()
        ->and(ExpectedStatus::isValid('ok'))->toBeFalse()
        ->and(ExpectedStatus::isValid('20'))->toBeFalse();
});

test('expected status matches the right codes', function () {
    expect(ExpectedStatus::matches('200', 200))->toBeTrue()
        ->and(ExpectedStatus::matches('200', 201))->toBeFalse()
        ->and(ExpectedStatus::matches('200,204', 204))->toBeTrue()
        ->and(ExpectedStatus::matches('200-299', 250))->toBeTrue()
        ->and(ExpectedStatus::matches('200-299', 300))->toBeFalse()
        ->and(ExpectedStatus::matches('2xx', 299))->toBeTrue()
        ->and(ExpectedStatus::matches('3xx', 200))->toBeFalse();
});

test('interval computes the next run for seconds and cron', function () {
    $from = Carbon::parse('2026-10-03 12:00:07', 'UTC');

    expect(Interval::next($from, 30, null)->toDateTimeString())->toBe('2026-10-03 12:00:37')
        ->and(Interval::next($from, 5, null)->toDateTimeString())->toBe('2026-10-03 12:00:17')
        ->and(Interval::next($from, null, '*/5 * * * *')->toDateTimeString())->toBe('2026-10-03 12:05:00')
        ->and(Interval::next($from, null, '@hourly')->toDateTimeString())->toBe('2026-10-03 13:00:00');
});

test('interval validates cron expressions and labels schedules', function () {
    expect(Interval::isValidCron('*/5 * * * *'))->toBeTrue()
        ->and(Interval::isValidCron('@daily'))->toBeTrue()
        ->and(Interval::isValidCron('every five minutes'))->toBeFalse()
        ->and(Interval::label(60, null))->toBe('Every minute')
        ->and(Interval::label(45, null))->toBe('Every 45 seconds')
        ->and(Interval::label(null, '0 * * * *'))->toBe('cron 0 * * * *');
});
