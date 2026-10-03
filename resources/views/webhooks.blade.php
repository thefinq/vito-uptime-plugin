@extends('uptime::layout', ['title' => 'Incoming webhooks'])

@section('actions')
  <a class="btn" href="{{ route('uptime.index') }}">Monitors</a>
@endsection

@section('content')
  <div class="card">
    <p class="muted" style="margin-top:0">
      An incoming webhook forwards events from <strong>Uptime Kuma</strong> to the notification
      channels configured in Vito. In Kuma create a notification of type <em>Webhook</em>, request
      body <em>application/json</em>, with the URL shown here, and attach it to the monitors you want.
    </p>
    <form method="post" action="{{ route('uptime.webhooks.store') }}" class="actions">
      @csrf
      <input type="text" name="name" placeholder="Name, e.g. Kuma on finqapi" value="{{ old('name') }}" required maxlength="100" style="max-width:320px">
      <button class="btn primary" type="submit">Create webhook</button>
    </form>
    @error('name')<div class="error">{{ $message }}</div>@enderror
  </div>

  <div class="card">
    @if ($webhooks->isEmpty())
      <p class="muted">No webhooks yet.</p>
    @else
      <table>
        <thead><tr><th>Name</th><th>URL</th><th>Received</th><th>Last event</th><th></th></tr></thead>
        <tbody>
        @foreach ($webhooks as $w)
          <tr>
            <td><strong>{{ $w->name }}</strong><br><span class="muted small">{{ $w->source }}</span></td>
            <td class="small">
              @if ($revealed === $w->id)
                <code style="word-break:break-all">{{ $w->url() }}</code>
              @else
                <code>{{ url('/uptime/hooks/') }}/…{{ substr($w->token, -6) }}</code>
                <form class="inline" method="post" action="{{ route('uptime.webhooks.rotate', $w) }}" onsubmit="return confirm('Rotate the token? The current URL stops working.')">@csrf<button class="btn" type="submit" style="margin-left:6px">Rotate &amp; reveal</button></form>
              @endif
            </td>
            <td class="small">{{ $w->received_count }}</td>
            <td class="small">
              @if ($w->last_received_at)
                {{ $w->last_received_at->diffForHumans() }}<br><span class="muted">{{ $w->last_message }}</span>
              @else
                <span class="muted">nothing yet</span>
              @endif
            </td>
            <td>
              <form class="inline" method="post" action="{{ route('uptime.webhooks.destroy', $w) }}" onsubmit="return confirm('Delete this webhook?')">@csrf @method('DELETE')<button class="btn danger" type="submit">Delete</button></form>
            </td>
          </tr>
        @endforeach
        </tbody>
      </table>
      <p class="muted small" style="margin-bottom:0">The full URL is shown once, right after creating or rotating. Anyone who knows it can post events, so treat it like a password.</p>
    @endif
  </div>
@endsection
