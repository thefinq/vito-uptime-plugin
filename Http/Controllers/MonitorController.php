<?php

namespace App\Vito\Plugins\Thefinq\VitoUptimePlugin\Http\Controllers;

use App\Models\Project;
use App\Vito\Plugins\Thefinq\VitoUptimePlugin\Models\Monitor;
use App\Vito\Plugins\Thefinq\VitoUptimePlugin\Models\MonitorEvent;
use App\Vito\Plugins\Thefinq\VitoUptimePlugin\Services\Checker;
use App\Vito\Plugins\Thefinq\VitoUptimePlugin\Services\ExpectedStatus;
use App\Vito\Plugins\Thefinq\VitoUptimePlugin\Services\Interval;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;

class MonitorController extends Controller
{
    public function index(Request $request): View
    {
        $project = $this->project($request);

        $monitors = Monitor::where('project_id', $project->id)
            ->orderByRaw("case state when 'down' then 0 when 'pending' then 1 else 2 end")
            ->orderBy('name')
            ->get();

        return view('uptime::index', [
            'project' => $project,
            'monitors' => $monitors,
        ]);
    }

    public function create(Request $request): View
    {
        return view('uptime::form', [
            'project' => $this->project($request),
            'monitor' => new Monitor(['method' => 'GET', 'expected_status' => '200', 'timeout' => 10, 'retries' => 2, 'enabled' => true, 'interval_seconds' => 60]),
            'presets' => Interval::presets(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $project = $this->project($request);
        $data = $this->validated($request);

        $monitor = new Monitor($data);
        $monitor->project_id = $project->id;
        $monitor->save();

        return redirect()->route('uptime.index')->with('status', "Monitor \"{$monitor->name}\" created. First check runs within a minute.");
    }

    public function show(Request $request, Monitor $monitor): View
    {
        $this->guard($request, $monitor);

        return view('uptime::show', [
            'project' => $monitor->project,
            'monitor' => $monitor,
            'events' => $monitor->events()->latest('created_at')->limit(100)->get(),
        ]);
    }

    public function edit(Request $request, Monitor $monitor): View
    {
        $this->guard($request, $monitor);

        return view('uptime::form', [
            'project' => $monitor->project,
            'monitor' => $monitor,
            'presets' => Interval::presets(),
        ]);
    }

    public function update(Request $request, Monitor $monitor): RedirectResponse
    {
        $this->guard($request, $monitor);
        $data = $this->validated($request);

        $monitor->fill($data);
        if ($monitor->isDirty(['interval_seconds', 'cron'])) {
            $monitor->next_check_at = null;
        }
        $monitor->save();

        return redirect()->route('uptime.show', $monitor)->with('status', 'Monitor updated.');
    }

    public function destroy(Request $request, Monitor $monitor): RedirectResponse
    {
        $this->guard($request, $monitor);

        $monitor->events()->delete();
        $monitor->delete();

        return redirect()->route('uptime.index')->with('status', "Monitor \"{$monitor->name}\" deleted.");
    }

    public function toggle(Request $request, Monitor $monitor): RedirectResponse
    {
        $this->guard($request, $monitor);

        $monitor->enabled = ! $monitor->enabled;
        if ($monitor->enabled) {
            $monitor->next_check_at = null;
            $monitor->consecutive_failures = 0;
            $monitor->state = Monitor::STATE_PENDING;
        }
        $monitor->save();

        $monitor->events()->create([
            'type' => $monitor->enabled ? MonitorEvent::TYPE_RESUMED : MonitorEvent::TYPE_PAUSED,
            'created_at' => now(),
        ]);

        return back()->with('status', $monitor->enabled ? 'Monitor resumed.' : 'Monitor paused.');
    }

    public function check(Request $request, Monitor $monitor, Checker $checker): RedirectResponse
    {
        $this->guard($request, $monitor);

        $result = $checker->run($monitor);

        return back()->with('status', $result->ok
            ? "Check passed: HTTP {$result->statusCode} in {$result->responseMs} ms."
            : "Check failed: {$result->error}");
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'url' => ['required', 'string', 'max:2048', 'url:http,https'],
            'method' => ['required', Rule::in(['GET', 'HEAD'])],
            'expected_status' => ['required', 'string', 'max:100', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! ExpectedStatus::isValid((string) $value)) {
                    $fail('Use status codes like 200, a list like 200,204, a range like 200-299, or 2xx.');
                }
            }],
            'keyword' => ['nullable', 'string', 'max:255'],
            'timeout' => ['required', 'integer', 'min:1', 'max:120'],
            'schedule' => ['required', Rule::in(array_keys(Interval::presets()))],
            'cron' => ['nullable', 'string', 'max:100', 'required_if:schedule,'.Interval::CUSTOM, function (string $attribute, mixed $value, \Closure $fail) use ($request): void {
                if ($request->input('schedule') === Interval::CUSTOM && ! Interval::isValidCron((string) $value)) {
                    $fail('Not a valid cron expression (five fields, e.g. */5 * * * *).');
                }
            }],
            'retries' => ['required', 'integer', 'min:1', 'max:10'],
            'enabled' => ['nullable', 'boolean'],
        ]);

        $custom = $data['schedule'] === Interval::CUSTOM;

        return [
            'name' => $data['name'],
            'url' => $data['url'],
            'method' => $data['method'],
            'expected_status' => str_replace(' ', '', $data['expected_status']),
            'keyword' => $data['keyword'] ?? null,
            'timeout' => (int) $data['timeout'],
            'interval_seconds' => $custom ? null : (int) $data['schedule'],
            'cron' => $custom ? trim((string) $data['cron']) : null,
            'retries' => (int) $data['retries'],
            'enabled' => (bool) ($data['enabled'] ?? false),
        ];
    }

    private function project(Request $request): Project
    {
        /** @var Project $project */
        $project = $request->user()->currentProject;

        return $project;
    }

    private function guard(Request $request, Monitor $monitor): void
    {
        abort_unless($monitor->project_id === $this->project($request)->id, 404);
    }
}
