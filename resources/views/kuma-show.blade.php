@extends('uptime::layout', ['title' => $connection->name])

@php use App\Vito\Plugins\Thefinq\VitoUptimePlugin\Services\Ui; @endphp

@section('crumb', $connection->name)
@section('description')
  Read from <code>{{ $connection->metricsUrl() }}</code>, cached for 20 s. Changes are made in Kuma.
@endsection

@section('actions')
  <form method="post" action="{{ route('uptime.kuma.refresh', $connection) }}">@csrf<button type="submit" class="{{ Ui::button() }}">Refresh</button></form>
  <a href="{{ $connection->addMonitorUrl() }}" target="_blank" rel="noopener" class="{{ Ui::button() }}">Add monitor in Kuma</a>
  <a href="{{ $connection->dashboardUrl() }}" target="_blank" rel="noopener" class="{{ Ui::buttonPrimary() }}">Open Kuma</a>
@endsection

@section('content')
  @if ($error)
    <div class="{{ Ui::card() }} border-red-500"><div class="{{ Ui::cardContent() }} text-sm"><span class="font-medium text-red-600">Could not read Uptime Kuma:</span> {{ $error }}</div></div>
  @else
    <div class="flex flex-wrap items-center gap-2 text-sm">
      <span class="{{ Ui::badge('down') }}">{{ $counts['down'] ?? 0 }} down</span>
      <span class="{{ Ui::badge('pending') }}">{{ $counts['pending'] ?? 0 }} pending</span>
      <span class="{{ Ui::badge('paused') }}">{{ $counts['maintenance'] ?? 0 }} maintenance</span>
      <span class="{{ Ui::badge('up') }}">{{ $counts['up'] ?? 0 }} up</span>
    </div>
    @if (empty($monitors))
      <div class="{{ Ui::card() }}"><div class="{{ Ui::cardContent() }} text-muted-foreground text-sm">Kuma reports no monitors.</div></div>
    @else
      <div class="{{ Ui::tableWrapper() }}">
        <div class="relative w-full overflow-x-auto">
          <table class="{{ Ui::table() }}">
            <thead class="{{ Ui::thead() }}"><tr class="{{ Ui::tr() }}"><th class="{{ Ui::th() }}">Status</th><th class="{{ Ui::th() }}">Monitor</th><th class="{{ Ui::th() }}">Type</th><th class="{{ Ui::th() }}">Response</th><th class="{{ Ui::th() }}">Certificate</th></tr></thead>
            <tbody>
            @foreach ($monitors as $m)
              <tr class="{{ Ui::tr() }}">
                <td class="{{ Ui::td() }}"><span class="{{ Ui::badge($m['status']) }}">{{ $m['status'] }}</span></td>
                <td class="{{ Ui::td() }}"><div class="font-medium">{{ $m['name'] }}</div>@if ($m['target'] !== '')<div class="text-muted-foreground text-xs">{{ $m['target'] }}</div>@endif</td>
                <td class="{{ Ui::td() }} text-xs">{{ $m['type'] }}</td>
                <td class="{{ Ui::td() }} text-xs">{{ $m['response_ms'] !== null ? $m['response_ms'].' ms' : '—' }}</td>
                <td class="{{ Ui::td() }} text-xs">
                  @if ($m['cert_days'] !== null)
                    {{ $m['cert_days'] }} days @if ($m['cert_valid'] === false) <span class="text-red-600">invalid</span> @endif
                  @else — @endif
                </td>
              </tr>
            @endforeach
            </tbody>
          </table>
        </div>
      </div>
    @endif
  @endif

  <div class="{{ Ui::card() }}">
    <details>
      <summary class="{{ Ui::cardHeader() }} cursor-pointer"><span class="{{ Ui::cardTitle() }}">Edit connection</span></summary>
      <form method="post" action="{{ route('uptime.kuma.update', $connection) }}">
        @csrf @method('PUT')
        <div class="{{ Ui::cardContent() }} grid gap-4 md:grid-cols-3">
          <div class="grid gap-2"><label for="name" class="{{ Ui::label() }}">Name</label><input type="text" id="name" name="name" value="{{ old('name', $connection->name) }}" required maxlength="100" class="{{ Ui::input() }}">@error('name')<div class="{{ Ui::error() }}">{{ $message }}</div>@enderror</div>
          <div class="grid gap-2"><label for="base_url" class="{{ Ui::label() }}">Base URL</label><input type="url" id="base_url" name="base_url" value="{{ old('base_url', $connection->base_url) }}" required class="{{ Ui::input() }}">@error('base_url')<div class="{{ Ui::error() }}">{{ $message }}</div>@enderror</div>
          <div class="grid gap-2"><label for="api_key" class="{{ Ui::label() }}">New API key</label><input type="password" id="api_key" name="api_key" autocomplete="off" placeholder="leave empty to keep the current key" class="{{ Ui::input() }}"></div>
          <label class="flex items-center gap-2 text-sm md:col-span-3"><input type="checkbox" name="verify_tls" value="1" @checked(old('verify_tls', $connection->verify_tls))> Verify the TLS certificate</label>
        </div>
        <div class="{{ Ui::cardFooter() }}">
          <button type="submit" class="{{ Ui::buttonPrimary() }}">Save</button>
        </div>
      </form>
      <form method="post" action="{{ route('uptime.kuma.destroy', $connection) }}" class="{{ Ui::cardFooter() }}" onsubmit="return confirm('Remove this connection from Vito? Nothing changes in Uptime Kuma.')">
        @csrf @method('DELETE')
        <button type="submit" class="{{ Ui::buttonDanger() }}">Remove connection</button>
      </form>
    </details>
  </div>
@endsection
