# Changelog

## 0.1.2 - 2026-10-03

- Fix `Route [uptime.create] not defined` on panels with a cached route table: route
  names are now set before the routes are registered.

## 0.1.1 - 2026-10-03

- The check command now keeps running while any monitor comes due within its budget,
  so monitors that are due mid-minute are no longer pushed to the next minute.

## 0.1.0 - 2026-10-03

First release.

- HTTP monitors (GET/HEAD) with expected status, keyword, timeout and retries.
- Interval presets from 10 seconds to 24 hours, or a cron expression.
- Down/up alerts through Vito's notification channels.
- Monitors page with pause, resume, check now and per-monitor history.
- `uptime:check` command scheduled every minute, serving sub-minute intervals.
