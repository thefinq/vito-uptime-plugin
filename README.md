# Uptime Monitor plugin for VitoDeploy

HTTP uptime checks inside [Vito](https://github.com/vitodeploy/vito) 4.x. Each monitor
runs on its own interval (from every 10 seconds up) or on a cron expression, and state
changes are delivered through the notification channels you already configured in Vito
(Telegram, Slack, Discord, e-mail).

- GET or HEAD checks with an expected status (`200`, `200,204`, `200-299`, `2xx`), an
  optional keyword that must appear in the body, and a timeout.
- Interval presets from 10 seconds to 24 hours, or any cron expression (`*/5 * * * *`,
  `@hourly`, ...).
- A configurable number of consecutive failures before a monitor counts as down, so a
  single hiccup does not page anyone.
- One alert when a monitor goes down, one when it comes back (with the downtime).
- Pause, resume, check now, and a history of state changes per monitor.
- Monitors belong to a project. Every member of the project sees the same list.

## Requirements

- Vito 4.1 or newer.
- Vito's scheduler running (it is part of a standard installation).
- The panel host must be able to reach the URLs you want to check.

## Installation

In Vito go to **Admin → Plugins → Install from GitHub** and enter

```
https://github.com/thefinq/vito-uptime-plugin
```

Vito downloads the latest release, installs it (two tables are created) and lets you
**Enable** it from the Installed tab.

## Usage

Open `https://your-panel/uptime`. You can also reach it from any server's **Features**
tab, where the plugin shows a summary and a link.

Add a monitor: name, URL, method, expected status, optional keyword, timeout, schedule,
and how many failed checks in a row are needed before the monitor is considered down.
The first check runs within a minute. Status, response time and the last error are shown
in the list; the monitor page keeps a history of down/up events.

### How scheduling works

Vito's scheduler starts `uptime:check` every minute. The command runs every monitor
that is due. When at least one enabled monitor has an interval under a minute, the
command keeps looping for the rest of that minute and serves those monitors on time
(a 10-second monitor is checked six times per minute). Cron expressions are evaluated
with minute granularity in UTC.

### Notifications

Alerts are sent with Vito's own notification system, to every channel configured under
**Settings → Notification Channels**. No extra configuration is needed.

## Running the checks by hand

```
php artisan uptime:check --once     # one pass over the due monitors
php artisan uptime:check            # the same loop the scheduler runs
```

## Development

Put a clone of this repository at `app/Vito/Plugins/Thefinq/VitoUptimePlugin` inside a
Vito checkout (a symlink works), then install and enable it from **Admin → Plugins →
Discover**. Tests use Vito's test case:

```
vendor/bin/pest app/Vito/Plugins/Thefinq/VitoUptimePlugin/tests
```

## Roadmap

- Webhook endpoint for Uptime Kuma, so Kuma incidents reach the same Vito channels.
- Uptime Kuma as an alternative backend with per-user credentials.

## License

MIT, see [LICENSE](LICENSE).
