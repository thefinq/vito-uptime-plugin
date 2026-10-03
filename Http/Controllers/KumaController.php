<?php

namespace App\Vito\Plugins\Thefinq\VitoUptimePlugin\Http\Controllers;

use App\Models\Project;
use App\Vito\Plugins\Thefinq\VitoUptimePlugin\Models\KumaConnection;
use App\Vito\Plugins\Thefinq\VitoUptimePlugin\Services\KumaClient;
use App\Vito\Plugins\Thefinq\VitoUptimePlugin\Services\KumaException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;

/**
 * Uptime Kuma as a backend: every user connects their own Kuma with their own API key.
 * Monitors and configuration live in Kuma; this is a read-only window plus deep links.
 */
class KumaController extends Controller
{
    private const int CACHE_SECONDS = 20;

    public function index(Request $request): View
    {
        return view('uptime::kuma', [
            'project' => $this->project($request),
            'connections' => KumaConnection::where('user_id', $request->user()->id)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, KumaClient $client): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'base_url' => ['required', 'string', 'max:2048', 'url:http,https'],
            'api_key' => ['required', 'string', 'max:255'],
            'verify_tls' => ['nullable', 'boolean'],
        ]);

        $connection = new KumaConnection([
            'name' => $data['name'],
            'base_url' => rtrim($data['base_url'], '/'),
            'api_key' => $data['api_key'],
            'verify_tls' => (bool) ($data['verify_tls'] ?? false),
        ]);
        $connection->user_id = $request->user()->id;

        try {
            $monitors = $client->monitors($connection);
        } catch (KumaException $e) {
            return back()->withInput()->withErrors(['base_url' => 'Could not read monitors: '.$e->getMessage()]);
        }

        $connection->last_ok_at = now();
        $connection->save();

        return redirect()->route('uptime.kuma.show', $connection)
            ->with('status', 'Connected to '.$connection->name.': '.count($monitors).' monitor(s).');
    }

    public function show(Request $request, KumaConnection $connection, KumaClient $client): View
    {
        $this->guard($request, $connection);

        $error = null;
        $monitors = [];

        try {
            $monitors = Cache::remember($this->cacheKey($connection), self::CACHE_SECONDS, fn () => $client->monitors($connection));
            $connection->forceFill(['last_ok_at' => now(), 'last_error' => null])->save();
        } catch (KumaException $e) {
            $error = $e->getMessage();
            $connection->forceFill(['last_error' => $error])->save();
        }

        return view('uptime::kuma-show', [
            'project' => $this->project($request),
            'connection' => $connection,
            'monitors' => $monitors,
            'error' => $error,
            'counts' => collect($monitors)->countBy('status'),
        ]);
    }

    public function refresh(Request $request, KumaConnection $connection): RedirectResponse
    {
        $this->guard($request, $connection);
        Cache::forget($this->cacheKey($connection));

        return redirect()->route('uptime.kuma.show', $connection);
    }

    public function update(Request $request, KumaConnection $connection): RedirectResponse
    {
        $this->guard($request, $connection);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'base_url' => ['required', 'string', 'max:2048', 'url:http,https'],
            'api_key' => ['nullable', 'string', 'max:255'],
            'verify_tls' => ['nullable', 'boolean'],
        ]);

        $connection->name = $data['name'];
        $connection->base_url = rtrim($data['base_url'], '/');
        $connection->verify_tls = (bool) ($data['verify_tls'] ?? false);
        if (! empty($data['api_key'])) {
            $connection->api_key = $data['api_key'];
        }
        $connection->save();
        Cache::forget($this->cacheKey($connection));

        return redirect()->route('uptime.kuma.show', $connection)->with('status', 'Connection updated.');
    }

    public function destroy(Request $request, KumaConnection $connection): RedirectResponse
    {
        $this->guard($request, $connection);
        Cache::forget($this->cacheKey($connection));
        $connection->delete();

        return redirect()->route('uptime.kuma.index')->with('status', "Connection \"{$connection->name}\" removed. Nothing changed in Uptime Kuma.");
    }

    private function cacheKey(KumaConnection $connection): string
    {
        return 'uptime-plugin:kuma:'.$connection->id;
    }

    private function project(Request $request): Project
    {
        /** @var Project $project */
        $project = $request->user()->currentProject;

        return $project;
    }

    private function guard(Request $request, KumaConnection $connection): void
    {
        abort_unless($connection->user_id === $request->user()->id, 404);
    }
}
