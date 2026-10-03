@extends('uptime::layout', ['title' => $monitor->exists ? 'Edit monitor' : 'Add monitor'])

@php
  $schedule = old('schedule', $monitor->usesCron() ? \App\Vito\Plugins\Thefinq\VitoUptimePlugin\Services\Interval::CUSTOM : (string) ($monitor->interval_seconds ?? 60));
@endphp

@section('content')
  <form method="post" action="{{ $monitor->exists ? route('uptime.update', $monitor) : route('uptime.store') }}">
    @csrf
    @if ($monitor->exists) @method('PUT') @endif

    <div class="card">
      <div class="grid">
        <div class="field">
          <label for="name">Name</label>
          <input type="text" id="name" name="name" value="{{ old('name', $monitor->name) }}" required maxlength="100">
          @error('name')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="field">
          <label for="method">Method</label>
          <select id="method" name="method">
            @foreach (['GET', 'HEAD'] as $method)
              <option value="{{ $method }}" @selected(old('method', $monitor->method) === $method)>{{ $method }}</option>
            @endforeach
          </select>
        </div>
      </div>
      <div class="field">
        <label for="url">URL</label>
        <input type="url" id="url" name="url" value="{{ old('url', $monitor->url) }}" required placeholder="https://example.com/health">
        @error('url')<div class="error">{{ $message }}</div>@enderror
      </div>
      <div class="grid">
        <div class="field">
          <label for="expected_status">Expected status</label>
          <input type="text" id="expected_status" name="expected_status" value="{{ old('expected_status', $monitor->expected_status) }}" required>
          <div class="hint">200 · 200,204 · 200-299 · 2xx</div>
          @error('expected_status')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="field">
          <label for="keyword">Keyword (optional)</label>
          <input type="text" id="keyword" name="keyword" value="{{ old('keyword', $monitor->keyword) }}" maxlength="255">
          <div class="hint">Text that must appear in the response body (case-insensitive).</div>
          @error('keyword')<div class="error">{{ $message }}</div>@enderror
        </div>
      </div>
    </div>

    <div class="card">
      <div class="grid">
        <div class="field">
          <label for="schedule">Check</label>
          <select id="schedule" name="schedule" onchange="document.getElementById('cron-field').style.display = this.value === 'cron' ? '' : 'none'">
            @foreach ($presets as $value => $label)
              <option value="{{ $value }}" @selected($schedule === (string) $value)>{{ $label }}</option>
            @endforeach
          </select>
          @error('schedule')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="field" id="cron-field" @if ($schedule !== 'cron') style="display:none" @endif>
          <label for="cron">Cron expression</label>
          <input type="text" id="cron" name="cron" value="{{ old('cron', $monitor->cron) }}" placeholder="*/5 * * * *">
          <div class="hint">Five fields, minute granularity, UTC. <code>@hourly</code>, <code>@daily</code> also work.</div>
          @error('cron')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="field">
          <label for="timeout">Timeout (seconds)</label>
          <input type="number" id="timeout" name="timeout" min="1" max="120" value="{{ old('timeout', $monitor->timeout) }}" required>
          @error('timeout')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="field">
          <label for="retries">Failures before alert</label>
          <input type="number" id="retries" name="retries" min="1" max="10" value="{{ old('retries', $monitor->retries) }}" required>
          <div class="hint">Consecutive failed checks needed before the monitor counts as down.</div>
          @error('retries')<div class="error">{{ $message }}</div>@enderror
        </div>
      </div>
      <div class="field">
        <label><input type="checkbox" name="enabled" value="1" @checked(old('enabled', $monitor->enabled))> Enabled</label>
      </div>
    </div>

    <div class="actions">
      <button class="btn primary" type="submit">{{ $monitor->exists ? 'Save' : 'Create monitor' }}</button>
      <a class="btn" href="{{ $monitor->exists ? route('uptime.show', $monitor) : route('uptime.index') }}">Cancel</a>
    </div>
  </form>
@endsection
