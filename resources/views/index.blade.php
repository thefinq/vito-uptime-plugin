@extends('uptime::layout', ['title' => 'Uptime monitors'])

@section('actions')
  <a class="btn" href="{{ route('uptime.webhooks.index') }}">Webhooks</a>
  <a class="btn primary" href="{{ route('uptime.create') }}">Add monitor</a>
@endsection

@section('content')
  <div class="card">
    @if ($monitors->isEmpty())
      <p class="muted">No monitors yet. Add one to start checking a URL.</p>
    @else
      <table>
        <thead>
          <tr><th>Status</th><th>Monitor</th><th>Schedule</th><th>Last check</th><th>Response</th><th></th></tr>
        </thead>
        <tbody>
        @foreach ($monitors as $m)
          <tr>
            <td>
              @if (! $m->enabled)
                <span class="pill paused">paused</span>
              @else
                <span class="pill {{ $m->state }}">{{ $m->state }}</span>
              @endif
            </td>
            <td>
              <a href="{{ route('uptime.show', $m) }}"><strong>{{ $m->name }}</strong></a><br>
              <span class="muted small">{{ $m->method }} {{ $m->url }}</span>
              @if ($m->isDown() && $m->last_error)
                <br><span class="small" style="color:var(--bad)">{{ $m->last_error }}</span>
              @endif
            </td>
            <td class="small">{{ $m->scheduleLabel() }}</td>
            <td class="small">
              @if ($m->last_checked_at)
                {{ $m->last_checked_at->diffForHumans() }}
              @else
                <span class="muted">never</span>
              @endif
            </td>
            <td class="small">
              @if ($m->last_status_code) HTTP {{ $m->last_status_code }} @endif
              @if ($m->last_response_ms !== null) · {{ $m->last_response_ms }} ms @endif
            </td>
            <td>
              <div class="actions">
                <form class="inline" method="post" action="{{ route('uptime.check', $m) }}">@csrf<button class="btn">Check now</button></form>
                <a class="btn" href="{{ route('uptime.edit', $m) }}">Edit</a>
              </div>
            </td>
          </tr>
        @endforeach
        </tbody>
      </table>
    @endif
  </div>
@endsection
