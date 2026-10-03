<?php

use App\Models\User;
use App\Vito\Plugins\Thefinq\VitoUptimePlugin\Models\KumaConnection;
use App\Vito\Plugins\Thefinq\VitoUptimePlugin\Plugin;
use App\Vito\Plugins\Thefinq\VitoUptimePlugin\Services\KumaClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $plugin = new Plugin;
    $plugin->install();
    $plugin->boot();
});

const KUMA_METRICS = <<<'TXT'
# HELP monitor_status Monitor Status (1 = UP, 0= DOWN, 2= PENDING, 3= MAINTENANCE)
# TYPE monitor_status gauge
monitor_status{monitor_name="API",monitor_type="http",monitor_url="https://api.example.test/up",monitor_hostname="null",monitor_port="null"} 1
monitor_status{monitor_name="Postgres",monitor_type="port",monitor_url="https://",monitor_hostname="10.0.0.5",monitor_port="5432"} 0
monitor_status{monitor_name="Quoted \"name\"",monitor_type="keyword",monitor_url="https://example.test",monitor_hostname="null",monitor_port="null"} 2
monitor_response_time{monitor_name="API",monitor_type="http",monitor_url="https://api.example.test/up",monitor_hostname="null",monitor_port="null"} 123.4
monitor_response_time{monitor_name="Postgres",monitor_type="port",monitor_url="https://",monitor_hostname="10.0.0.5",monitor_port="5432"} -1
monitor_cert_days_remaining{monitor_name="API",monitor_type="http",monitor_url="https://api.example.test/up",monitor_hostname="null",monitor_port="null"} 61
monitor_cert_is_valid{monitor_name="API",monitor_type="http",monitor_url="https://api.example.test/up",monitor_hostname="null",monitor_port="null"} 1
TXT;

test('kuma metrics are parsed into monitors, worst first', function () {
    $monitors = KumaClient::parse(KUMA_METRICS);

    expect($monitors)->toHaveCount(3)
        ->and($monitors[0])->toMatchArray(['name' => 'Postgres', 'type' => 'port', 'target' => '10.0.0.5:5432', 'status' => 'down', 'response_ms' => null])
        ->and($monitors[1])->toMatchArray(['name' => 'Quoted "name"', 'status' => 'pending'])
        ->and($monitors[2])->toMatchArray(['name' => 'API', 'target' => 'https://api.example.test/up', 'status' => 'up', 'response_ms' => 123, 'cert_days' => 61, 'cert_valid' => true]);
});

test('a user can connect their own kuma and see its monitors', function () {
    Http::fake(['kuma.example.test/*' => Http::response(KUMA_METRICS, 200)]);
    $this->actingAs($this->user);

    $this->get(route('uptime.kuma.index'))->assertOk()->assertSee('No Kuma connections yet');

    $this->post(route('uptime.kuma.store'), [
        'name' => 'My Kuma',
        'base_url' => 'https://kuma.example.test/',
        'api_key' => 'uk1_secret',
        'verify_tls' => 1,
    ])->assertRedirect();

    $connection = KumaConnection::firstOrFail();
    expect($connection->user_id)->toBe($this->user->id)
        ->and($connection->base_url)->toBe('https://kuma.example.test')
        ->and($connection->api_key)->toBe('uk1_secret')
        ->and($connection->getRawOriginal('api_key'))->not->toBe('uk1_secret');

    Http::assertSent(fn ($request) => $request->url() === 'https://kuma.example.test/metrics' && $request->hasHeader('Authorization'));

    $this->get(route('uptime.kuma.show', $connection))->assertOk()
        ->assertSee('Postgres')->assertSee('10.0.0.5:5432')->assertSee('1 down')->assertSee('https://kuma.example.test/dashboard');

    $this->put(route('uptime.kuma.update', $connection), ['name' => 'Renamed', 'base_url' => 'https://kuma.example.test', 'api_key' => '', 'verify_tls' => 0])->assertRedirect();
    expect($connection->refresh()->name)->toBe('Renamed')->and($connection->api_key)->toBe('uk1_secret')->and($connection->verify_tls)->toBeFalse();

    $this->delete(route('uptime.kuma.destroy', $connection))->assertRedirect(route('uptime.kuma.index'));
    expect(KumaConnection::count())->toBe(0);
});

test('a kuma connection that cannot be read is not saved', function () {
    Http::fake(['kuma.example.test/*' => Http::response('Unauthorized', 401)]);
    $this->actingAs($this->user);

    $this->from(route('uptime.kuma.index'))->post(route('uptime.kuma.store'), [
        'name' => 'Bad', 'base_url' => 'https://kuma.example.test', 'api_key' => 'wrong',
    ])->assertRedirect(route('uptime.kuma.index'))->assertSessionHasErrors('base_url');

    expect(KumaConnection::count())->toBe(0);
});

test('kuma connections are private to their user', function () {
    Http::fake(['kuma.example.test/*' => Http::response(KUMA_METRICS, 200)]);
    $other = User::factory()->create();
    $connection = KumaConnection::create(['user_id' => $other->id, 'name' => 'Theirs', 'base_url' => 'https://kuma.example.test', 'api_key' => 'k', 'verify_tls' => true]);

    $this->actingAs($this->user);
    $this->get(route('uptime.kuma.index'))->assertOk()->assertDontSee('Theirs');
    $this->get(route('uptime.kuma.show', $connection))->assertNotFound();
    $this->delete(route('uptime.kuma.destroy', $connection))->assertNotFound();
});
