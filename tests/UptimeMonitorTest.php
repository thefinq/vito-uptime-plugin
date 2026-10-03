<?php

use App\Models\Project;
use App\Plugins\RegisterCommand;
use App\Vito\Plugins\Thefinq\VitoUptimePlugin\Models\Monitor;
use App\Vito\Plugins\Thefinq\VitoUptimePlugin\Models\MonitorEvent;
use App\Vito\Plugins\Thefinq\VitoUptimePlugin\Models\Webhook;
use App\Vito\Plugins\Thefinq\VitoUptimePlugin\Notifications\MonitorDown;
use App\Vito\Plugins\Thefinq\VitoUptimePlugin\Notifications\MonitorUp;
use App\Vito\Plugins\Thefinq\VitoUptimePlugin\Notifications\WebhookEvent;
use App\Vito\Plugins\Thefinq\VitoUptimePlugin\Plugin;
use App\Vito\Plugins\Thefinq\VitoUptimePlugin\Services\Checker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $plugin = new Plugin;
    $plugin->install();
    $plugin->boot();

    // In production Vito registers plugin commands itself; the test app booted before this plugin did.
    foreach (RegisterCommand::get() as $command) {
        Artisan::registerCommand(app($command));
    }
});

function makeMonitor(array $attributes = []): Monitor
{
    return Monitor::create(array_merge([
        'project_id' => test()->user->currentProject->id,
        'name' => 'Site',
        'url' => 'https://example.test/health',
        'method' => 'GET',
        'expected_status' => '200',
        'timeout' => 5,
        'interval_seconds' => 60,
        'retries' => 2,
        'enabled' => true,
    ], $attributes));
}

test('plugin registers its server feature, command and routes', function () {
    expect(config('server.features.uptime.actions.open.handler'))->not->toBeNull()
        ->and(route('uptime.index'))->toEndWith('/uptime');
});

test('a monitor goes down after the configured failures and alerts once', function () {
    Notification::fake();
    Http::fake(['example.test/*' => Http::response('nope', 503)]);

    $monitor = makeMonitor(['retries' => 2]);
    $checker = app(Checker::class);

    $checker->run($monitor);
    expect($monitor->refresh()->state)->toBe(Monitor::STATE_PENDING)
        ->and($monitor->consecutive_failures)->toBe(1);
    Notification::assertNothingSent();

    $checker->run($monitor);
    expect($monitor->refresh()->state)->toBe(Monitor::STATE_DOWN)
        ->and($monitor->last_status_code)->toBe(503)
        ->and($monitor->last_error)->toBe('HTTP 503, expected 200')
        ->and($monitor->events()->where('type', MonitorEvent::TYPE_DOWN)->count())->toBe(1);
    Notification::assertSentTo($this->notificationChannel, MonitorDown::class);

    $checker->run($monitor);
    expect($monitor->refresh()->consecutive_failures)->toBe(3);
    Notification::assertSentToTimes($this->notificationChannel, MonitorDown::class, 1);
});

test('a monitor recovers and reports the downtime', function () {
    Notification::fake();
    Http::fake(['example.test/*' => Http::response('ok', 200)]);

    $monitor = makeMonitor();
    $monitor->forceFill(['state' => Monitor::STATE_DOWN, 'consecutive_failures' => 4, 'last_changed_at' => now()->subMinutes(3)])->save();

    app(Checker::class)->run($monitor);

    expect($monitor->refresh()->state)->toBe(Monitor::STATE_UP)
        ->and($monitor->consecutive_failures)->toBe(0)
        ->and($monitor->events()->where('type', MonitorEvent::TYPE_UP)->value('duration_seconds'))->toBeGreaterThanOrEqual(179);
    Notification::assertSentTo($this->notificationChannel, MonitorUp::class);
});

test('keyword and status class checks are applied', function () {
    Notification::fake();
    Http::fake(['example.test/*' => Http::response('<h1>Maintenance</h1>', 200)]);

    $monitor = makeMonitor(['expected_status' => '2xx', 'keyword' => 'welcome', 'retries' => 1]);
    $result = app(Checker::class)->run($monitor);

    expect($result->ok)->toBeFalse()
        ->and($result->error)->toContain('Keyword "welcome" not found')
        ->and($monitor->refresh()->state)->toBe(Monitor::STATE_DOWN);
});

test('connection errors count as failures', function () {
    Notification::fake();
    Http::fake(['example.test/*' => fn () => throw new ConnectionException('cURL error 7: refused')]);

    $monitor = makeMonitor(['retries' => 1]);
    app(Checker::class)->run($monitor);

    expect($monitor->refresh()->state)->toBe(Monitor::STATE_DOWN)
        ->and($monitor->last_status_code)->toBeNull()
        ->and($monitor->last_error)->toContain('refused');
});

test('the check command only runs due monitors', function () {
    Notification::fake();
    Http::fake(['example.test/*' => Http::response('ok', 200)]);

    $due = makeMonitor(['name' => 'Due']);
    $later = makeMonitor(['name' => 'Later']);
    $later->forceFill(['next_check_at' => now()->addMinutes(10)])->save();
    $paused = makeMonitor(['name' => 'Paused', 'enabled' => false]);

    $this->artisan('uptime:check', ['--once' => true])->assertSuccessful();

    expect($due->refresh()->last_checked_at)->not->toBeNull()
        ->and($due->next_check_at->greaterThan(now()->addSeconds(50)))->toBeTrue()
        ->and($later->refresh()->last_checked_at)->toBeNull()
        ->and($paused->refresh()->last_checked_at)->toBeNull();
});

test('pages require a logged in user', function () {
    $this->get(route('uptime.index'))->assertRedirect(url('/'));
});

test('monitors can be listed, created, edited, toggled and deleted', function () {
    $this->actingAs($this->user);

    $this->get(route('uptime.index'))->assertOk()->assertSee('No monitors yet');

    $this->post(route('uptime.store'), [
        'name' => 'API',
        'url' => 'https://api.example.test/up',
        'method' => 'HEAD',
        'expected_status' => '200, 204',
        'keyword' => '',
        'timeout' => 10,
        'schedule' => 'cron',
        'cron' => '*/5 * * * *',
        'retries' => 3,
        'enabled' => 1,
    ])->assertRedirect(route('uptime.index'));

    $monitor = Monitor::firstOrFail();
    expect($monitor->project_id)->toBe($this->user->currentProject->id)
        ->and($monitor->cron)->toBe('*/5 * * * *')
        ->and($monitor->interval_seconds)->toBeNull()
        ->and($monitor->expected_status)->toBe('200,204')
        ->and($monitor->method)->toBe('HEAD');

    $this->get(route('uptime.index'))->assertOk()->assertSee('API')->assertSee('cron */5 * * * *');
    $this->get(route('uptime.show', $monitor))->assertOk()->assertSee('HEAD https://api.example.test/up');
    $this->get(route('uptime.edit', $monitor))->assertOk()->assertSee('*/5 * * * *');

    $this->put(route('uptime.update', $monitor), [
        'name' => 'API',
        'url' => 'https://api.example.test/up',
        'method' => 'GET',
        'expected_status' => '200',
        'timeout' => 10,
        'schedule' => '30',
        'cron' => '',
        'retries' => 2,
        'enabled' => 1,
    ])->assertRedirect(route('uptime.show', $monitor));
    expect($monitor->refresh()->interval_seconds)->toBe(30)->and($monitor->cron)->toBeNull();

    $this->post(route('uptime.toggle', $monitor))->assertRedirect();
    expect($monitor->refresh()->enabled)->toBeFalse()
        ->and($monitor->events()->where('type', MonitorEvent::TYPE_PAUSED)->count())->toBe(1);

    $this->delete(route('uptime.destroy', $monitor))->assertRedirect(route('uptime.index'));
    expect(Monitor::count())->toBe(0)->and(MonitorEvent::count())->toBe(0);
});

test('invalid input is rejected', function () {
    $this->actingAs($this->user);

    $this->from(route('uptime.create'))->post(route('uptime.store'), [
        'name' => 'Bad',
        'url' => 'ftp://example.test',
        'method' => 'POST',
        'expected_status' => 'ok',
        'timeout' => 0,
        'schedule' => 'cron',
        'cron' => 'every five minutes',
        'retries' => 0,
    ])->assertRedirect(route('uptime.create'))
        ->assertSessionHasErrors(['url', 'method', 'expected_status', 'timeout', 'cron', 'retries']);

    expect(Monitor::count())->toBe(0);
});

test('monitors of another project are not visible', function () {
    $this->actingAs($this->user);

    $other = Project::factory()->create();
    $monitor = makeMonitor(['project_id' => $other->id]);

    $this->get(route('uptime.show', $monitor))->assertNotFound();
    $this->get(route('uptime.index'))->assertOk()->assertDontSee('https://example.test/health');
});

test('check now runs the monitor immediately', function () {
    Notification::fake();
    Http::fake(['example.test/*' => Http::response('ok', 200)]);
    $this->actingAs($this->user);

    $monitor = makeMonitor();

    $this->from(route('uptime.show', $monitor))->post(route('uptime.check', $monitor))
        ->assertRedirect(route('uptime.show', $monitor))
        ->assertSessionHas('status', fn (string $s) => str_starts_with($s, 'Check passed: HTTP 200'));

    expect($monitor->refresh()->state)->toBe(Monitor::STATE_UP);
});

test('an Uptime Kuma webhook is forwarded to the notification channels', function () {
    Notification::fake();
    $this->actingAs($this->user);

    $this->post(route('uptime.webhooks.store'), ['name' => 'Kuma'])->assertRedirect(route('uptime.webhooks.index'));
    $webhook = Webhook::firstOrFail();
    expect($webhook->project_id)->toBe($this->user->currentProject->id)->and(strlen($webhook->token))->toBe(40);

    $this->get(route('uptime.webhooks.index'))->assertOk()->assertSee($webhook->url());

    $down = [
        'heartbeat' => ['monitorID' => 3, 'status' => 0, 'msg' => 'timeout of 48000ms exceeded', 'time' => '2026-10-03 15:00:00'],
        'monitor' => ['id' => 3, 'name' => 'API', 'url' => 'https://api.example.test/up', 'type' => 'http'],
        'msg' => '[API] [🔴 Down] timeout of 48000ms exceeded',
    ];
    $this->postJson($webhook->url(), $down)->assertOk()->assertJson(['ok' => true, 'level' => 'down']);
    Notification::assertSentTo($this->notificationChannel, WebhookEvent::class, function ($n) {
        return $n->rawText() === '🔴 DOWN: API (https://api.example.test/up) — timeout of 48000ms exceeded [Kuma]';
    });

    $up = ['heartbeat' => ['status' => 1, 'msg' => '200 - OK'], 'monitor' => ['name' => 'DB', 'hostname' => '10.0.0.5', 'port' => 5432], 'msg' => 'x'];
    $this->postJson($webhook->url(), $up)->assertOk()->assertJson(['level' => 'up']);
    Notification::assertSentTo($this->notificationChannel, WebhookEvent::class, fn ($n) => $n->rawText() === '🟢 UP: DB (10.0.0.5:5432) — 200 - OK [Kuma]');

    $this->postJson($webhook->url(), ['heartbeat' => null, 'monitor' => null, 'msg' => 'Testing'])->assertOk()->assertJson(['level' => 'info']);
    Notification::assertSentTo($this->notificationChannel, WebhookEvent::class, fn ($n) => $n->rawText() === 'Testing [Kuma]');

    expect($webhook->refresh()->received_count)->toBe(3)->and($webhook->last_message)->toBe('Testing');

    $this->postJson(route('uptime.hooks.receive', ['token' => 'nope']), $down)->assertNotFound();
    Notification::assertSentToTimes($this->notificationChannel, WebhookEvent::class, 3);

    $old = $webhook->token;
    $this->post(route('uptime.webhooks.rotate', $webhook))->assertRedirect(route('uptime.webhooks.index'));
    expect($webhook->refresh()->token)->not->toBe($old);
    $this->postJson(route('uptime.hooks.receive', ['token' => $old]), $down)->assertNotFound();

    $this->delete(route('uptime.webhooks.destroy', $webhook))->assertRedirect(route('uptime.webhooks.index'));
    expect(Webhook::count())->toBe(0);
});

test('webhooks of another project are not reachable from the page', function () {
    $this->actingAs($this->user);
    $other = Project::factory()->create();
    $webhook = Webhook::create(['project_id' => $other->id, 'name' => 'x', 'source' => 'kuma', 'token' => Webhook::generateToken()]);

    $this->post(route('uptime.webhooks.rotate', $webhook))->assertNotFound();
    $this->delete(route('uptime.webhooks.destroy', $webhook))->assertNotFound();
    $this->get(route('uptime.webhooks.index'))->assertOk()->assertDontSee(substr($webhook->token, -6));
});
