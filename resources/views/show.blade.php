@extends('uptime::layout', ['title' => $monitor->name])

@php use App\Vito\Plugins\Thefinq\VitoUptimePlugin\Services\Ui; @endphp

@section('crumb', $monitor->name)

@section('actions')
  <form method="post" action="{{ route('uptime.check', $monitor) }}">@csrf<button type="submit" class="{{ Ui::button() }}">Check now</button></form>
  <form method="post" action="{{ route('uptime.toggle', $monitor) }}">@csrf<button type="submit" class="{{ Ui::button() }}">{{ $monitor->enabled ? 'Pause' : 'Resume' }}</button></form>
  <a href="{{ route('uptime.edit', $monitor) }}" class="{{ Ui::button() }}">Edit</a>
  <form method="post" action="{{ route('uptime.destroy', $monitor) }}" onsubmit="return confirm('Delete this monitor and its history?')">@csrf @method('DELETE')<button type="submit" class="{{ Ui::buttonDanger() }}">Delete</button></form>
@endsection

@section('content')
  <div class="{{ Ui::card() }}">
    <div class="{{ Ui::cardContent() }}">
      <dl class="grid gap-x-6 gap-y-3 text-sm md:grid-cols-[160px_1fr]">
        <dt class="text-muted-foreground">Status</dt>
        <dd>
          @if (! $monitor->enabled)
            <span class="{{ Ui::badge('paused') }}">paused</span>
          @else
            <span class="{{ Ui::badge($monitor->state) }}">{{ $monitor->state }}</span>
            @if ($monitor->isDown() && $monitor->last_changed_at) <span class="text-muted-foreground text-xs">since {{ $monitor->last_changed_at->toDateTimeString() }} ({{ $monitor->last_changed_at->diffForHumans() }})</span> @endif
          @endif
        </dd>
        <dt class="text-muted-foreground">Request</dt><dd><code class="text-xs">{{ $monitor->method }} {{ $monitor->url }}</code></dd>
        <dt class="text-muted-foreground">Expects</dt><dd>HTTP {{ $monitor->expected_status }} @if ($monitor->keyword) · body contains "{{ $monitor->keyword }}" @endif · timeout {{ $monitor->timeout }} s</dd>
        <dt class="text-muted-foreground">Schedule</dt><dd>{{ $monitor->scheduleLabel() }} · alert after {{ $monitor->retries }} failure(s)</dd>
        <dt class="text-muted-foreground">Last check</dt>
        <dd>
          @if ($monitor->last_checked_at)
            {{ $monitor->last_checked_at->toDateTimeString() }} ({{ $monitor->last_checked_at->diffForHumans() }})
            @if ($monitor->last_status_code) · HTTP {{ $monitor->last_status_code }} @endif
            @if ($monitor->last_response_ms !== null) · {{ $monitor->last_response_ms }} ms @endif
            @if ($monitor->last_error) <div class="text-xs text-red-600">{{ $monitor->last_error }}</div> @endif
          @else
            <span class="text-muted-foreground">not yet</span>
          @endif
        </dd>
        <dt class="text-muted-foreground">Next check</dt><dd>{{ $monitor->next_check_at ? $monitor->next_check_at->toDateTimeString() : 'within a minute' }}</dd>
        <dt class="text-muted-foreground">Consecutive failures</dt><dd>{{ $monitor->consecutive_failures }}</dd>
      </dl>
    </div>
  </div>

  <div class="{{ Ui::card() }}">
    <div class="{{ Ui::cardHeader() }}"><div class="{{ Ui::cardTitle() }}">History</div><div class="{{ Ui::cardDescription() }}">State changes, newest first.</div></div>
    @if ($events->isEmpty())
      <div class="{{ Ui::cardContent() }} text-muted-foreground text-sm">No state changes yet.</div>
    @else
      <div class="relative w-full overflow-x-auto">
        <table class="{{ Ui::table() }}">
          <thead class="{{ Ui::thead() }}"><tr class="{{ Ui::tr() }}"><th class="{{ Ui::th() }}">When</th><th class="{{ Ui::th() }}">Event</th><th class="{{ Ui::th() }}">Details</th></tr></thead>
          <tbody>
          @foreach ($events as $e)
            <tr class="{{ Ui::tr() }}">
              <td class="{{ Ui::td() }} text-xs whitespace-nowrap">{{ $e->created_at->toDateTimeString() }}</td>
              <td class="{{ Ui::td() }}"><span class="{{ Ui::badge($e->type === 'down' ? 'down' : ($e->type === 'up' ? 'up' : 'paused')) }}">{{ $e->type }}</span></td>
              <td class="{{ Ui::td() }} text-xs">
                {{ $e->message }}
                @if ($e->duration_seconds !== null) <span class="text-muted-foreground">· down for {{ \Carbon\CarbonInterval::seconds($e->duration_seconds)->cascade()->forHumans(['short' => true]) }}</span> @endif
              </td>
            </tr>
          @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>
@endsection
