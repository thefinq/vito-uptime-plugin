<?php

namespace App\Vito\Plugins\Thefinq\VitoUptimePlugin;

use App\Plugins\AbstractPlugin;
use App\Plugins\RegisterCommand;
use App\Plugins\RegisterServerFeature;
use App\Plugins\RegisterServerFeatureAction;
use App\Plugins\RegisterViews;
use App\Vito\Plugins\Thefinq\VitoUptimePlugin\Console\CheckMonitorsCommand;
use App\Vito\Plugins\Thefinq\VitoUptimePlugin\Features\OpenMonitorsAction;
use App\Vito\Plugins\Thefinq\VitoUptimePlugin\Http\Controllers\WebhookController;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Throwable;

class Plugin extends AbstractPlugin
{
    public const string VERSION = '0.3.0';

    private const string MIGRATED_KEY = 'uptime-plugin:migrated-version';

    protected string $name = 'Uptime Monitor';

    protected string $description = 'HTTP uptime checks with per-monitor intervals or cron schedules, incoming webhooks from Uptime Kuma, and a read-only view of your own Uptime Kuma monitors. Alerts go through the notification channels configured in Vito.';

    public function boot(): void
    {
        $this->migrateAfterUpdate();

        RegisterViews::make('uptime')
            ->path(__DIR__.'/resources/views')
            ->register();

        // Vito loads registered view paths after booting plugins; make the namespace
        // available right away as well, so the views work wherever boot() is called.
        if (! View::exists('uptime::layout')) {
            View::addNamespace('uptime', __DIR__.'/resources/views');
        }

        // The shell mirrors Vito's sidebar and header and needs the current user.
        View::composer('uptime::shell', function (\Illuminate\View\View $view): void {
            $user = auth()->user();
            $initials = collect(preg_split('/\s+/', trim((string) $user?->name)) ?: [])
                ->filter()->take(2)->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');

            $view->with(['uptimeUser' => $user, 'uptimeUserInitials' => $initials !== '' ? $initials : 'U']);
        });

        RegisterCommand::make(CheckMonitorsCommand::class)->register();

        if (! config('server.features.uptime')) {
            RegisterServerFeature::make('uptime')
                ->label('Uptime monitors')
                ->description('HTTP checks for this project, managed on the Uptime page.')
                ->register();

            RegisterServerFeatureAction::make('uptime', 'open')
                ->label('Open')
                ->handler(OpenMonitorsAction::class)
                ->register();

            $this->registerRoutes();
            $this->registerSchedule();
        }
    }

    public function install(): void
    {
        $this->migrate();
    }

    public function enable(): void
    {
        $this->migrate();
    }

    public function uninstall(): void
    {
        Schema::dropIfExists('uptime_monitor_events');
        Schema::dropIfExists('uptime_monitors');
        Schema::dropIfExists('uptime_webhooks');
        Schema::dropIfExists('uptime_kuma_connections');
    }

    private function registerRoutes(): void
    {
        Route::middleware(['web', 'auth', 'has-project'])
            ->prefix('uptime')
            ->name('uptime.')
            ->where(['monitor' => '[0-9]+', 'webhook' => '[0-9]+', 'connection' => '[0-9]+'])
            ->group(__DIR__.'/routes.php');

        // Inbound webhooks: no session, no CSRF, rate limited; the token in the URL is the secret.
        Route::middleware(['api'])
            ->prefix('uptime')
            ->name('uptime.')
            ->group(function (): void {
                Route::name('hooks.receive')->post('/hooks/{token}', [WebhookController::class, 'receive']);
            });
    }

    private function registerSchedule(): void
    {
        if (! app()->runningInConsole()) {
            return;
        }

        // Plugins boot after the application has booted, so the schedule already exists.
        app(Schedule::class)
            ->command('uptime:check')
            ->everyMinute()
            ->withoutOverlapping(5)
            ->runInBackground();
    }

    /**
     * Vito runs install() and enable() but has no hook for updates, so a new release
     * applies its migrations the first time it boots. The marker lives in the cache.
     */
    private function migrateAfterUpdate(): void
    {
        try {
            if (Cache::get(self::MIGRATED_KEY) === self::VERSION) {
                return;
            }

            $this->migrate();
            Cache::forever(self::MIGRATED_KEY, self::VERSION);
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function migrate(): void
    {
        Artisan::call('migrate', [
            '--path' => __DIR__.'/Database/Migrations',
            '--realpath' => true,
            '--force' => true,
        ]);

        Cache::forever(self::MIGRATED_KEY, self::VERSION);
    }
}
