@extends('uptime::layout', ['title' => $monitor->exists ? 'Edit monitor' : 'Add monitor'])

@php
  use App\Vito\Plugins\Thefinq\VitoUptimePlugin\Services\Interval;
  use App\Vito\Plugins\Thefinq\VitoUptimePlugin\Services\Ui;
  $schedule = old('schedule', $monitor->usesCron() ? Interval::CUSTOM : (string) ($monitor->interval_seconds ?? 60));
@endphp

@section('crumb', $monitor->exists ? $monitor->name : 'New')

@section('content')
  <form method="post" action="{{ $monitor->exists ? route('uptime.update', $monitor) : route('uptime.store') }}" class="space-y-5">
    @csrf
    @if ($monitor->exists) @method('PUT') @endif

    <div class="{{ Ui::card() }}">
      <div class="{{ Ui::cardHeader() }}">
        <div class="{{ Ui::cardTitle() }}">Request</div>
        <div class="{{ Ui::cardDescription() }}">What to call and what counts as healthy.</div>
      </div>
      <div class="{{ Ui::cardContent() }} grid gap-4 md:grid-cols-2">
        <div class="grid gap-2">
          <label for="name" class="{{ Ui::label() }}">Name</label>
          <input type="text" id="name" name="name" value="{{ old('name', $monitor->name) }}" required maxlength="100" class="{{ Ui::input() }}">
          @error('name')<div class="{{ Ui::error() }}">{{ $message }}</div>@enderror
        </div>
        <div class="grid gap-2">
          <label for="method" class="{{ Ui::label() }}">Method</label>
          <select id="method" name="method" class="{{ Ui::select() }}">
            @foreach (['GET', 'HEAD'] as $method)
              <option value="{{ $method }}" @selected(old('method', $monitor->method) === $method)>{{ $method }}</option>
            @endforeach
          </select>
        </div>
        <div class="grid gap-2 md:col-span-2">
          <label for="url" class="{{ Ui::label() }}">URL</label>
          <input type="url" id="url" name="url" value="{{ old('url', $monitor->url) }}" required placeholder="https://example.com/health" class="{{ Ui::input() }}">
          @error('url')<div class="{{ Ui::error() }}">{{ $message }}</div>@enderror
        </div>
        <div class="grid gap-2">
          <label for="expected_status" class="{{ Ui::label() }}">Expected status</label>
          <input type="text" id="expected_status" name="expected_status" value="{{ old('expected_status', $monitor->expected_status) }}" required class="{{ Ui::input() }}">
          <div class="{{ Ui::hint() }}">200 · 200,204 · 200-299 · 2xx</div>
          @error('expected_status')<div class="{{ Ui::error() }}">{{ $message }}</div>@enderror
        </div>
        <div class="grid gap-2">
          <label for="keyword" class="{{ Ui::label() }}">Keyword (optional)</label>
          <input type="text" id="keyword" name="keyword" value="{{ old('keyword', $monitor->keyword) }}" maxlength="255" class="{{ Ui::input() }}">
          <div class="{{ Ui::hint() }}">Text that must appear in the response body (case-insensitive).</div>
          @error('keyword')<div class="{{ Ui::error() }}">{{ $message }}</div>@enderror
        </div>
      </div>
    </div>

    <div class="{{ Ui::card() }}">
      <div class="{{ Ui::cardHeader() }}">
        <div class="{{ Ui::cardTitle() }}">Schedule and alerting</div>
        <div class="{{ Ui::cardDescription() }}">How often to check, and when to call it down.</div>
      </div>
      <div class="{{ Ui::cardContent() }} grid gap-4 md:grid-cols-2">
        <div class="grid gap-2">
          <label for="schedule" class="{{ Ui::label() }}">Check</label>
          <select id="schedule" name="schedule" class="{{ Ui::select() }}" onchange="document.getElementById('cron-field').style.display = this.value === 'cron' ? '' : 'none'">
            @foreach ($presets as $value => $label)
              <option value="{{ $value }}" @selected($schedule === (string) $value)>{{ $label }}</option>
            @endforeach
          </select>
          @error('schedule')<div class="{{ Ui::error() }}">{{ $message }}</div>@enderror
        </div>
        <div class="grid gap-2" id="cron-field" @if ($schedule !== 'cron') style="display:none" @endif>
          <label for="cron" class="{{ Ui::label() }}">Cron expression</label>
          <input type="text" id="cron" name="cron" value="{{ old('cron', $monitor->cron) }}" placeholder="*/5 * * * *" class="{{ Ui::input() }}">
          <div class="{{ Ui::hint() }}">Five fields, minute granularity, UTC. <code>@hourly</code>, <code>@daily</code> also work.</div>
          @error('cron')<div class="{{ Ui::error() }}">{{ $message }}</div>@enderror
        </div>
        <div class="grid gap-2">
          <label for="timeout" class="{{ Ui::label() }}">Timeout (seconds)</label>
          <input type="number" id="timeout" name="timeout" min="1" max="120" value="{{ old('timeout', $monitor->timeout) }}" required class="{{ Ui::input() }}">
          @error('timeout')<div class="{{ Ui::error() }}">{{ $message }}</div>@enderror
        </div>
        <div class="grid gap-2">
          <label for="retries" class="{{ Ui::label() }}">Failures before alert</label>
          <input type="number" id="retries" name="retries" min="1" max="10" value="{{ old('retries', $monitor->retries) }}" required class="{{ Ui::input() }}">
          <div class="{{ Ui::hint() }}">Consecutive failed checks needed before the monitor counts as down.</div>
          @error('retries')<div class="{{ Ui::error() }}">{{ $message }}</div>@enderror
        </div>
        <label class="flex items-center gap-2 text-sm md:col-span-2"><input type="checkbox" name="enabled" value="1" @checked(old('enabled', $monitor->enabled))> Enabled</label>
      </div>
      <div class="{{ Ui::cardFooter() }}">
        <button type="submit" class="{{ Ui::buttonPrimary() }}">{{ $monitor->exists ? 'Save' : 'Create monitor' }}</button>
        <a href="{{ $monitor->exists ? route('uptime.show', $monitor) : route('uptime.index') }}" class="{{ Ui::button() }}">Cancel</a>
      </div>
    </div>
  </form>
@endsection
