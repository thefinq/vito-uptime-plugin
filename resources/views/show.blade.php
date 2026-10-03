@extends('uptime::layout', ['title' => $monitor->name])

@section('actions')
  <form class="inline" method="post" action="{{ route('uptime.check', $monitor) }}">@csrf<button class="btn">Check now</button></form>
  <form class="inline" method="post" action="{{ route('uptime.toggle', $monitor) }}">@csrf<button class="btn">{{ $monitor->enabled ? 'Pause' : 'Resume' }}</button></form>
  <a class="btn" href="{{ route('uptime.edit', $monitor) }}">Edit</a>
  <form class="inline" method="post" action="{{ route('uptime.destroy', $monitor) }}" onsubmit="return confirm('Delete this monitor and its history?')">@csrf @method('DELETE')<button class="btn danger">Delete</button></form>
@endsection

@section('content')
  <div class="card">
    <dl class="kv">
      <dt>Status</dt>
      <dd>
        @if (! $monitor->enabled)
          <span class="pill paused">paused</span>
        @else
          <span class="pill {{ $monitor->state }}">{{ $monitor->state }}</span>
          @if ($monitor->isDown() && $monitor->last_changed_at) <span class="muted small">since {{ $monitor->last_changed_at->toDateTimeString() }} ({{ $monitor->last_changed_at->diffForHumans() }})</span> @endif
        @endif
      </dd>
      <dt>Request</dt><dd><code>{{ $monitor->method }} {{ $monitor->url }}</code></dd>
      <dt>Expects</dt><dd>HTTP {{ $monitor->expected_status }} @if ($monitor->keyword) · body contains "{{ $monitor->keyword }}" @endif · timeout {{ $monitor->timeout }} s</dd>
      <dt>Schedule</dt><dd>{{ $monitor->scheduleLabel() }} · alert after {{ $monitor->retries }} failure(s)</dd>
      <dt>Last check</dt>
      <dd>
        @if ($monitor->last_checked_at)
          {{ $monitor->last_checked_at->toDateTimeString() }} ({{ $monitor->last_checked_at->diffForHumans() }})
          @if ($monitor->last_status_code) · HTTP {{ $monitor->last_status_code }} @endif
          @if ($monitor->last_response_ms !== null) · {{ $monitor->last_response_ms }} ms @endif
          @if ($monitor->last_error) <br><span style="color:var(--bad)">{{ $monitor->last_error }}</span> @endif
        @else
          <span class="muted">not yet</span>
        @endif
      </dd>
      <dt>Next check</dt><dd>{{ $monitor->next_check_at ? $monitor->next_check_at->toDateTimeString() : 'within a minute' }}</dd>
      <dt>Consecutive failures</dt><dd>{{ $monitor->consecutive_failures }}</dd>
    </dl>
  </div>

  <div class="card">
    <h3 style="margin:0 0 10px">History</h3>
    @if ($events->isEmpty())
      <p class="muted">No state changes yet.</p>
    @else
      <table>
        <thead><tr><th>When</th><th>Event</th><th>Details</th></tr></thead>
        <tbody>
        @foreach ($events as $e)
          <tr>
            <td class="small">{{ $e->created_at->toDateTimeString() }}</td>
            <td><span class="pill {{ $e->type === 'down' ? 'down' : ($e->type === 'up' ? 'up' : 'paused') }}">{{ $e->type }}</span></td>
            <td class="small">
              {{ $e->message }}
              @if ($e->duration_seconds !== null) · down for {{ \Carbon\CarbonInterval::seconds($e->duration_seconds)->cascade()->forHumans(['short' => true]) }} @endif
            </td>
          </tr>
        @endforeach
        </tbody>
      </table>
    @endif
  </div>
@endsection
