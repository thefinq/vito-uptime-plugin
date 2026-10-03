@extends('uptime::layout', ['title' => 'Uptime monitors'])

@php use App\Vito\Plugins\Thefinq\VitoUptimePlugin\Services\Ui; @endphp

@section('description', 'HTTP checks for this project. Alerts go through the notification channels configured in Vito.')

@section('actions')
  <a href="{{ route('uptime.create') }}" class="{{ Ui::buttonPrimary() }}">Add monitor</a>
@endsection

@section('content')
  @if ($monitors->isEmpty())
    <div class="{{ Ui::card() }}"><div class="{{ Ui::cardContent() }} text-muted-foreground text-sm">No monitors yet. Add one to start checking a URL.</div></div>
  @else
    <div class="{{ Ui::tableWrapper() }}">
      <div class="relative w-full overflow-x-auto">
        <table class="{{ Ui::table() }}">
          <thead class="{{ Ui::thead() }}">
            <tr class="{{ Ui::tr() }}">
              <th class="{{ Ui::th() }}">Status</th>
              <th class="{{ Ui::th() }}">Monitor</th>
              <th class="{{ Ui::th() }}">Schedule</th>
              <th class="{{ Ui::th() }}">Last check</th>
              <th class="{{ Ui::th() }}">Response</th>
              <th class="{{ Ui::th() }}"></th>
            </tr>
          </thead>
          <tbody>
          @foreach ($monitors as $m)
            <tr class="{{ Ui::tr() }}">
              <td class="{{ Ui::td() }}">
                @if (! $m->enabled)
                  <span class="{{ Ui::badge('paused') }}">paused</span>
                @else
                  <span class="{{ Ui::badge($m->state) }}">{{ $m->state }}</span>
                @endif
              </td>
              <td class="{{ Ui::td() }}">
                <a href="{{ route('uptime.show', $m) }}" class="font-medium hover:underline">{{ $m->name }}</a>
                <div class="text-muted-foreground text-xs">{{ $m->method }} {{ $m->url }}</div>
                @if ($m->isDown() && $m->last_error)
                  <div class="text-xs text-red-600">{{ $m->last_error }}</div>
                @endif
              </td>
              <td class="{{ Ui::td() }} text-muted-foreground text-xs whitespace-nowrap">{{ $m->scheduleLabel() }}</td>
              <td class="{{ Ui::td() }} text-muted-foreground text-xs whitespace-nowrap">{{ $m->last_checked_at ? $m->last_checked_at->diffForHumans() : 'never' }}</td>
              <td class="{{ Ui::td() }} text-xs whitespace-nowrap">
                @if ($m->last_status_code) HTTP {{ $m->last_status_code }} @endif
                @if ($m->last_response_ms !== null) <span class="text-muted-foreground">· {{ $m->last_response_ms }} ms</span> @endif
              </td>
              <td class="{{ Ui::td() }}">
                <div class="flex justify-end gap-2">
                  <form method="post" action="{{ route('uptime.check', $m) }}">@csrf<button type="submit" class="{{ Ui::buttonSmall() }}">Check now</button></form>
                  <a href="{{ route('uptime.edit', $m) }}" class="{{ Ui::buttonSmall() }}">Edit</a>
                </div>
              </td>
            </tr>
          @endforeach
          </tbody>
        </table>
      </div>
    </div>
  @endif
@endsection
