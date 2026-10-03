<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    {{-- Appearance works like Vito's own pages: the choice lives in localStorage ("appearance"). --}}
    <script>
        (function () {
            let appearance = 'system';
            try { appearance = localStorage.getItem('appearance') || appearance; } catch (e) {}
            if (appearance === 'dark' || (appearance === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
    <style>
        html { background-color: oklch(1 0 0); }
        html.dark { background-color: oklch(0.145 0 0); }
    </style>
    <title>{{ $title ?? 'Uptime' }} - {{ config('app.name', 'Vito') }}</title>
    <link rel="icon" href="{{ asset('favicon/favicon-96x96.png') }}" sizes="any" />
    <link rel="preconnect" href="https://fonts.bunny.net" />
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    {{-- Vito's compiled stylesheet, resolved through the Vite manifest so it follows updates --}}
    <link rel="stylesheet" href="{{ \Illuminate\Support\Facades\Vite::asset('resources/css/app.css') }}" />
</head>
<body class="selection:bg-brand bg-background text-foreground min-h-svh font-sans antialiased selection:text-white">
    <header class="bg-background flex h-12 items-center justify-between gap-4 border-b px-4">
        <div class="flex items-center gap-3 text-sm">
            <a href="{{ url('/servers') }}" class="{{ \App\Vito\Plugins\Thefinq\VitoUptimePlugin\Services\Ui::buttonSmall() }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-left"><path d="m12 19-7-7 7-7"></path><path d="M19 12H5"></path></svg>
                {{ config('app.name', 'Vito') }}
            </a>
            <span class="text-muted-foreground">/</span>
            <span class="font-medium">{{ $project->name }}</span>
            <span class="text-muted-foreground">/</span>
            <a href="{{ route('uptime.index') }}" class="font-medium hover:underline">Uptime</a>
        </div>
        <div class="text-muted-foreground flex items-center gap-2 text-xs">
            <span class="bg-accent text-accent-foreground border-ring flex size-7 items-center justify-center rounded-md border text-xs">{{ $uptimeUserInitials }}</span>
            <span class="hidden sm:inline">{{ $uptimeUser->name }}</span>
        </div>
    </header>
    <main class="flex flex-1 flex-col">
        @yield('body')
    </main>
</body>
</html>
