<?php

namespace App\Vito\Plugins\Thefinq\VitoUptimePlugin;

use App\Plugins\AbstractPlugin;
use App\Plugins\RegisterCommand;
use App\Plugins\RegisterServerFeature;
use App\Plugins\RegisterServerFeatureAction;
use App\Plugins\RegisterViews;
use App\Vito\Plugins\Thefinq\VitoUptimePlugin\Console\CheckMonitorsCommand;
use App\Vito\Plugins\Thefinq\VitoUptimePlugin\Features\OpenMonitorsAction;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

class Plugin extends AbstractPlugin
{
    public const string VERSION = '0.1.2';

    protected string $name = 'Uptime Monitor';

    protected string $description = 'HTTP uptime checks with per-monitor intervals or cron schedules. Alerts go through the notification channels configured in Vito.';

    public function boot(): void
    {
        RegisterViews::make('uptime')
            ->path(__DIR__.'/resources/views')
            ->register();

        // Vito loads registered view paths after booting plugins; make the namespace
        // available right away as well, so the views work wherever boot() is called.
        if (! View::exists('uptime::layout')) {
            View::addNamespace('uptime', __DIR__.'/resources/views');
        }

        RegisterCommand::make(CheckMonitorsCommand::class)->register();

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
    }

    private function registerRoutes(): void
    {
        Route::middleware(['web', 'auth', 'has-project'])
            ->prefix('uptime')
            ->name('uptime.')
            ->group(__DIR__.'/routes.php');
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

    private function migrate(): void
    {
        Artisan::call('migrate', [
            '--path' => __DIR__.'/Database/Migrations',
            '--realpath' => true,
            '--force' => true,
        ]);
    }
}
