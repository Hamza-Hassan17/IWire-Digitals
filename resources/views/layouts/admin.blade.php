<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Dashboard') · iWire Control Room</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0..1,0&display=swap">
<link rel="stylesheet" href="{{ asset('css/iwire.css') }}">
<script src="{{ asset('js/iwire.js') }}"></script>
@stack('head')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
</head>
<body>
@php
    $current = request()->route()?->getName() ?? '';
    $active = fn (array $names) => collect($names)->contains(fn ($n) => str_starts_with($current, $n));
@endphp
<div class="app" x-data="shell" @keydown.escape.window="notif=false; menu=false">

  <aside class="side" :class="collapsed && 'collapsed'">
    <div class="brand">
      <div class="logo"><i style="height:8px"></i><i style="height:16px"></i><i style="height:22px"></i><i style="height:12px"></i><i style="height:18px"></i><i style="height:9px"></i></div>
      <div class="brand-text"><div class="brand-name">iWire</div><div class="brand-sub">Control Room</div></div>
    </div>
    <nav class="nav">
      @foreach ($nav as $group => $items)
        <div class="nav-group">
          <div class="nav-label">{{ $group }}</div>
          @foreach ($items as $it)
            <a href="{{ route($it['route']) }}" title="{{ $it['label'] }}" class="nav-item {{ $active($it['match']) ? 'active' : '' }}">
              <span class="ms">{{ $it['icon'] }}</span>
              <span class="nav-text">{{ $it['label'] }}</span>
              @if (!empty($it['badge']))<span class="nav-badge {{ !empty($it['orange']) ? 'orange' : '' }}">{{ $it['badge'] }}</span>@endif
            </a>
          @endforeach
        </div>
      @endforeach
    </nav>
    <div class="side-foot">
      <button type="button" @click="toggleSide()" class="nav-item" style="font-size:13px">
        <span class="ms" x-text="collapsed ? 'left_panel_open' : 'left_panel_close'">left_panel_close</span>
        <span class="nav-text">Collapse</span>
      </button>
    </div>
  </aside>

  <div class="content">
    <header class="top">
      <div class="grow"></div>

      <div style="position:relative" @click.outside="notif=false">
        <button type="button" class="icon-btn" @click="notif=!notif; menu=false" title="Notifications">
          <span class="ms" style="font-size:21px">notifications</span>
          @if ($notifications->where('unread', true)->count())
            <span style="position:absolute;top:9px;right:10px;width:8px;height:8px;border-radius:50%;background:var(--blue);box-shadow:0 0 0 2px #0B1E3A"></span>
          @endif
        </button>
        <div class="pop" style="width:380px;max-width:calc(100vw - 32px)" x-show="notif" x-cloak>
          <div class="row between" style="padding:16px 18px 10px"><div class="b fs15">Notifications</div></div>
          @forelse ($notifications as $n)
            <a href="{{ $n['url'] }}" class="notif">
              <div style="width:36px;height:36px;border-radius:12px;flex:none;display:flex;align-items:center;justify-content:center;background:{{ $n['bg'] }}"><span class="ms" style="font-size:19px;color:{{ $n['color'] }}">{{ $n['icon'] }}</span></div>
              <div class="grow"><div style="font-size:13px;line-height:1.4;color:#E6EEF8">{{ $n['text'] }}</div><div class="faint" style="font-size:11.5px;margin-top:3px">{{ $n['time'] }}</div></div>
            </a>
          @empty
            <div class="muted fs13" style="padding:12px 18px 18px">You’re all caught up.</div>
          @endforelse
        </div>
      </div>

      <div style="position:relative" @click.outside="menu=false">
        <button type="button" @click="menu=!menu; notif=false" class="row" style="gap:10px;padding:4px 10px 4px 4px;border-radius:24px;background:rgba(15,35,64,.6);border:1px solid var(--line)">
          <div class="avatar" style="width:34px;height:34px;background:linear-gradient(135deg,#0A9BFF,#0B3D7A);font-size:13px">{{ \App\Support\Iw::initials(auth()->user()->name) }}</div>
          <span class="sb fs13">Admin</span>
          <span class="ms muted" style="font-size:18px">expand_more</span>
        </button>
        <div class="pop" style="width:240px;padding:8px;border-radius:18px" x-show="menu" x-cloak>
          <div style="padding:10px 12px 12px;border-bottom:1px solid rgba(255,255,255,.06);margin-bottom:6px"><div class="sb fs14">{{ auth()->user()->name }}</div><div class="fs12 muted">{{ auth()->user()->email }}</div></div>
          <a href="{{ route('settings') }}" class="pop-item"><span class="ms">person</span>Profile & settings</a>
          <form method="POST" action="{{ route('logout') }}">@csrf<button class="pop-item"><span class="ms">logout</span>Sign out</button></form>
        </div>
      </div>
    </header>

    <main class="main">
      @yield('content')
    </main>

    {{-- Toast --}}
    <div class="toast" style="bottom:28px" x-show="$store.toast.on" x-cloak x-transition.opacity>
      <div class="toast-ico" :style="`background:${$store.toast.bg}`"><span class="ms" :style="`color:${$store.toast.fg}`" x-text="$store.toast.icon"></span></div>
      <div style="font-size:13.5px;font-weight:500" x-text="$store.toast.msg"></div>
      <button type="button" class="link" style="margin-left:8px;font-size:12.5px" @click="$store.toast.on=false">Dismiss</button>
    </div>
  </div>
</div>

@if (session('toast'))
<script>document.addEventListener('alpine:initialized', () => Alpine.store('toast').show(@json(session('toast')[0]), @json(session('toast')[1] ?? 'ok')));</script>
@endif
@stack('scripts')
</body>
</html>
