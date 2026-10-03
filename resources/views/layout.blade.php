<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $title ?? 'Uptime' }} · {{ config('app.name') }}</title>
<style>
  :root { --bg:#f7f8fa; --card:#fff; --ink:#111827; --muted:#6b7280; --line:#e5e7eb; --accent:#2563eb; --ok:#16a34a; --bad:#dc2626; --warn:#d97706; --pill:#f3f4f6; }
  @media (prefers-color-scheme: dark) { :root { --bg:#0b0f14; --card:#141a22; --ink:#e5e7eb; --muted:#9ca3af; --line:#263040; --accent:#60a5fa; --ok:#22c55e; --bad:#f87171; --warn:#fbbf24; --pill:#1f2937; } }
  * { box-sizing:border-box }
  body { margin:0; background:var(--bg); color:var(--ink); font:14px/1.5 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif; }
  a { color:var(--accent); text-decoration:none } a:hover { text-decoration:underline }
  main { max-width:1040px; margin:0 auto; padding:24px 16px 48px }
  .top { display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:18px }
  .top h1 { font-size:20px; margin:0 } .top .crumbs { color:var(--muted); font-size:13px }
  .card { background:var(--card); border:1px solid var(--line); border-radius:10px; padding:16px 18px; margin-bottom:14px }
  table { width:100%; border-collapse:collapse } th, td { text-align:left; padding:9px 8px; border-bottom:1px solid var(--line); vertical-align:top }
  th { color:var(--muted); font-weight:600; font-size:12px; text-transform:uppercase; letter-spacing:.03em } tr:last-child td { border-bottom:0 }
  .pill { display:inline-block; padding:2px 9px; border-radius:999px; font-size:12px; font-weight:600; background:var(--pill) }
  .pill.up { color:var(--ok) } .pill.down { color:var(--bad) } .pill.pending { color:var(--warn) } .pill.paused { color:var(--muted) }
  .muted { color:var(--muted) } .small { font-size:12px } code { font-family:ui-monospace,Menlo,monospace; font-size:12px }
  .btn { display:inline-block; border:1px solid var(--line); background:var(--card); color:var(--ink); border-radius:7px; padding:6px 12px; font:inherit; cursor:pointer }
  .btn.primary { background:var(--accent); border-color:var(--accent); color:#fff } .btn.danger { color:var(--bad) } .btn:hover { filter:brightness(.97) }
  form.inline { display:inline } .actions { display:flex; gap:6px; flex-wrap:wrap }
  .flash { border-left:4px solid var(--accent); padding:8px 12px; margin-bottom:14px; background:var(--card); border-radius:6px }
  .grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:12px 18px }
  label { display:block; font-weight:600; margin:0 0 4px } .hint { color:var(--muted); font-size:12px; margin-top:3px }
  input[type=text], input[type=url], input[type=number], select { width:100%; padding:7px 9px; border:1px solid var(--line); border-radius:7px; background:var(--bg); color:var(--ink); font:inherit }
  .error { color:var(--bad); font-size:12px; margin-top:3px } .field { margin-bottom:14px }
  .kv { display:grid; grid-template-columns:160px 1fr; gap:6px 12px } .kv dt { color:var(--muted) } .kv dd { margin:0 }
</style>
</head>
<body>
<main>
  <div class="top">
    <div>
      <div class="crumbs"><a href="{{ route('servers') }}">{{ config('app.name') }}</a> · {{ $project->name }} · <a href="{{ route('uptime.index') }}">Uptime</a></div>
      <h1>{{ $title ?? 'Uptime monitors' }}</h1>
    </div>
    <div class="actions">@yield('actions')</div>
  </div>
  @if (session('status'))
    <div class="flash">{{ session('status') }}</div>
  @endif
  @yield('content')
  <p class="muted small">Checks run from this panel host every minute; intervals under a minute are served inside that minute. Alerts go to the notification channels configured in Vito. Times are UTC.</p>
</main>
</body>
</html>
