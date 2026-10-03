@extends('uptime::layout', ['title' => 'Uptime Kuma'])

@php use App\Vito\Plugins\Thefinq\VitoUptimePlugin\Services\Ui; @endphp

@section('crumb', 'Uptime Kuma')
@section('description', 'Connect your own Uptime Kuma with an API key. Monitors stay in Kuma; this is a read-only view with links back.')

@section('content')
  <div class="{{ Ui::card() }}">
    <div class="{{ Ui::cardHeader() }}">
      <div class="{{ Ui::cardTitle() }}">Connect an instance</div>
      <div class="{{ Ui::cardDescription() }}">Create the key in Kuma under Settings → API Keys. Connections are personal: other users of this panel do not see yours. To get Kuma's alerts into Vito's channels, use an incoming <a href="{{ route('uptime.webhooks.index') }}" class="underline">webhook</a>.</div>
    </div>
    <form method="post" action="{{ route('uptime.kuma.store') }}">
      @csrf
      <div class="{{ Ui::cardContent() }} grid gap-4 md:grid-cols-3">
        <div class="grid gap-2">
          <label for="name" class="{{ Ui::label() }}">Name</label>
          <input type="text" id="name" name="name" value="{{ old('name') }}" required maxlength="100" placeholder="Kuma on finqapi" class="{{ Ui::input() }}">
          @error('name')<div class="{{ Ui::error() }}">{{ $message }}</div>@enderror
        </div>
        <div class="grid gap-2">
          <label for="base_url" class="{{ Ui::label() }}">Base URL</label>
          <input type="url" id="base_url" name="base_url" value="{{ old('base_url') }}" required placeholder="https://uptime.example.com" class="{{ Ui::input() }}">
          @error('base_url')<div class="{{ Ui::error() }}">{{ $message }}</div>@enderror
        </div>
        <div class="grid gap-2">
          <label for="api_key" class="{{ Ui::label() }}">API key</label>
          <input type="password" id="api_key" name="api_key" required autocomplete="off" class="{{ Ui::input() }}">
          <div class="{{ Ui::hint() }}">Stored encrypted; used only to read <code>/metrics</code>.</div>
          @error('api_key')<div class="{{ Ui::error() }}">{{ $message }}</div>@enderror
        </div>
        <label class="flex items-center gap-2 text-sm md:col-span-3"><input type="checkbox" name="verify_tls" value="1" @checked(old('verify_tls', true))> Verify the TLS certificate</label>
      </div>
      <div class="{{ Ui::cardFooter() }}"><button type="submit" class="{{ Ui::buttonPrimary() }}">Connect</button></div>
    </form>
  </div>

  @if ($connections->isEmpty())
    <div class="{{ Ui::card() }}"><div class="{{ Ui::cardContent() }} text-muted-foreground text-sm">No Kuma connections yet.</div></div>
  @else
    <div class="{{ Ui::tableWrapper() }}">
      <div class="relative w-full overflow-x-auto">
        <table class="{{ Ui::table() }}">
          <thead class="{{ Ui::thead() }}"><tr class="{{ Ui::tr() }}"><th class="{{ Ui::th() }}">Name</th><th class="{{ Ui::th() }}">URL</th><th class="{{ Ui::th() }}">Last read</th><th class="{{ Ui::th() }}"></th></tr></thead>
          <tbody>
          @foreach ($connections as $c)
            <tr class="{{ Ui::tr() }}">
              <td class="{{ Ui::td() }}"><a href="{{ route('uptime.kuma.show', $c) }}" class="font-medium hover:underline">{{ $c->name }}</a></td>
              <td class="{{ Ui::td() }} text-xs"><code>{{ $c->base_url }}</code></td>
              <td class="{{ Ui::td() }} text-xs">
                @if ($c->last_error) <span class="text-red-600">{{ $c->last_error }}</span>
                @elseif ($c->last_ok_at) {{ $c->last_ok_at->diffForHumans() }}
                @else <span class="text-muted-foreground">never</span> @endif
              </td>
              <td class="{{ Ui::td() }}"><div class="flex justify-end"><a href="{{ route('uptime.kuma.show', $c) }}" class="{{ Ui::buttonSmall() }}">Open</a></div></td>
            </tr>
          @endforeach
          </tbody>
        </table>
      </div>
    </div>
  @endif
@endsection
