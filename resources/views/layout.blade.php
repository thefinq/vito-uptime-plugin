@extends('uptime::shell')

@php use App\Vito\Plugins\Thefinq\VitoUptimePlugin\Services\Ui; @endphp

@section('body')
  <div class="container mx-auto space-y-5 px-4 py-5 max-w-5xl">
    <div class="flex items-start justify-between gap-4">
      <div class="space-y-0.5">
        <nav class="text-muted-foreground mb-1 flex items-center gap-1 text-xs">
          <a href="{{ route('uptime.index') }}" class="hover:text-foreground">Uptime</a>
          @hasSection('crumb')
            <span>/</span><span class="text-foreground">@yield('crumb')</span>
          @endif
        </nav>
        <h2 class="text-xl font-semibold tracking-tight">{{ $title ?? 'Uptime monitors' }}</h2>
        @hasSection('description')
          <p class="text-muted-foreground text-sm">@yield('description')</p>
        @endif
      </div>
      <div class="flex flex-wrap items-center justify-end gap-2">
        <a href="{{ route('uptime.index') }}" class="{{ Ui::button() }} @if(request()->routeIs('uptime.index')) bg-muted @endif">Monitors</a>
        <a href="{{ route('uptime.webhooks.index') }}" class="{{ Ui::button() }} @if(request()->routeIs('uptime.webhooks.*')) bg-muted @endif">Webhooks</a>
        <a href="{{ route('uptime.kuma.index') }}" class="{{ Ui::button() }} @if(request()->routeIs('uptime.kuma.*')) bg-muted @endif">Uptime Kuma</a>
        @yield('actions')
      </div>
    </div>

    @if (session('status'))
      <div class="bg-card flex items-start gap-3 rounded-xl border p-4 text-sm shadow-xs">
        <span class="{{ Ui::dot('up') }} mt-1.5"></span>
        <div>{{ session('status') }}</div>
      </div>
    @endif

    @yield('content')

    <p class="text-muted-foreground text-xs">Checks run from the panel host every minute; intervals under a minute are served inside that minute. Alerts go to the notification channels configured in Vito. Times are UTC.</p>
  </div>
@endsection
