@extends('uptime::layout', ['title' => 'Incoming webhooks'])

@php use App\Vito\Plugins\Thefinq\VitoUptimePlugin\Services\Ui; @endphp

@section('crumb', 'Webhooks')
@section('description', 'Forward events from Uptime Kuma to the notification channels configured in Vito.')

@section('content')
  <div class="{{ Ui::card() }}">
    <div class="{{ Ui::cardHeader() }}">
      <div class="{{ Ui::cardTitle() }}">New webhook</div>
      <div class="{{ Ui::cardDescription() }}">In Kuma create a notification of type <em>Webhook</em>, request body <em>application/json</em>, with the URL shown after creating, and attach it to the monitors you want.</div>
    </div>
    <form method="post" action="{{ route('uptime.webhooks.store') }}" class="{{ Ui::cardContent() }} flex flex-wrap items-end gap-3">
      @csrf
      <div class="grid gap-2 min-w-64">
        <label for="name" class="{{ Ui::label() }}">Name</label>
        <input type="text" id="name" name="name" placeholder="Kuma on finqapi" value="{{ old('name') }}" required maxlength="100" class="{{ Ui::input() }}">
        @error('name')<div class="{{ Ui::error() }}">{{ $message }}</div>@enderror
      </div>
      <button type="submit" class="{{ Ui::buttonPrimary() }}">Create webhook</button>
    </form>
  </div>

  @if ($webhooks->isEmpty())
    <div class="{{ Ui::card() }}"><div class="{{ Ui::cardContent() }} text-muted-foreground text-sm">No webhooks yet.</div></div>
  @else
    <div class="{{ Ui::tableWrapper() }}">
      <div class="relative w-full overflow-x-auto">
        <table class="{{ Ui::table() }}">
          <thead class="{{ Ui::thead() }}"><tr class="{{ Ui::tr() }}"><th class="{{ Ui::th() }}">Name</th><th class="{{ Ui::th() }}">URL</th><th class="{{ Ui::th() }}">Received</th><th class="{{ Ui::th() }}">Last event</th><th class="{{ Ui::th() }}"></th></tr></thead>
          <tbody>
          @foreach ($webhooks as $w)
            <tr class="{{ Ui::tr() }}">
              <td class="{{ Ui::td() }}"><div class="font-medium">{{ $w->name }}</div><div class="text-muted-foreground text-xs">{{ $w->source }}</div></td>
              <td class="{{ Ui::td() }} text-xs">
                @if ($revealed === $w->id)
                  <code class="break-all">{{ $w->url() }}</code>
                @else
                  <code>{{ url('/uptime/hooks/') }}/…{{ substr($w->token, -6) }}</code>
                  <form method="post" action="{{ route('uptime.webhooks.rotate', $w) }}" class="mt-2" onsubmit="return confirm('Rotate the token? The current URL stops working.')">@csrf<button type="submit" class="{{ Ui::buttonSmall() }}">Rotate &amp; reveal</button></form>
                @endif
              </td>
              <td class="{{ Ui::td() }} text-xs">{{ $w->received_count }}</td>
              <td class="{{ Ui::td() }} text-xs">
                @if ($w->last_received_at)
                  {{ $w->last_received_at->diffForHumans() }}<div class="text-muted-foreground">{{ $w->last_message }}</div>
                @else
                  <span class="text-muted-foreground">nothing yet</span>
                @endif
              </td>
              <td class="{{ Ui::td() }}">
                <form method="post" action="{{ route('uptime.webhooks.destroy', $w) }}" class="flex justify-end" onsubmit="return confirm('Delete this webhook?')">@csrf @method('DELETE')<button type="submit" class="{{ Ui::buttonSmall() }} text-red-600">Delete</button></form>
              </td>
            </tr>
          @endforeach
          </tbody>
        </table>
      </div>
    </div>
    <p class="text-muted-foreground text-xs">The full URL is shown once, right after creating or rotating. Anyone who knows it can post events, so treat it like a password.</p>
  @endif
@endsection
