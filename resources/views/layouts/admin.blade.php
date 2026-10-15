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
<script>window.IW_SEARCH = @json(route('search'));</script>
<script src="{{ asset('js/iwire.js') }}"></script>
@stack('head')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
</head>
<body>
@php
    $current = request()->route()?->getName() ?? '';
    $active = fn (array $names) => collect($names)->contains(fn ($n) => str_starts_with($current, $n));
@endphp
<div class="app" x-data="shell" @keydown.window="key($event)">

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
              @if (!empty($it['badge']))<span class="nav-badge {{ $it['route'] === 'reports.index' ? 'orange' : '' }}">{{ $it['badge'] }}</span>@endif
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
      <button type="button" class="search-btn" @click="openPalette()">
        <span class="ms" style="color:var(--muted)">search</span>
        <span class="grow ell">Search tracks, films, creators, listeners…</span>
        <span class="row g4"><span class="kbd">⌘</span><span class="kbd">K</span></span>
      </button>
      <div class="grow"></div>
      <form method="POST" action="{{ route('context') }}" class="seg top-ctx" style="align-self:center">
        @csrf
        @foreach ([['All', 'apps'], ['Music', 'graphic_eq'], ['Films', 'movie']] as [$label, $icon])
          <button name="ctx" value="{{ $label }}" class="{{ $ctx === $label ? 'on' : '' }}"><span class="ms">{{ $icon }}</span>{{ $label }}</button>
        @endforeach
      </form>

      <div style="position:relative" @click.outside="notif=false">
        <button type="button" class="icon-btn" @click="notif=!notif; menu=false">
          <span class="ms" style="font-size:21px">notifications</span>
          @if ($notifications->where('unread', true)->count())
            <span style="position:absolute;top:9px;right:10px;width:8px;height:8px;border-radius:50%;background:var(--blue);box-shadow:0 0 0 2px #0B1E3A"></span>
          @endif
        </button>
        <div class="pop" style="width:380px" x-show="notif" x-cloak>
          <div class="row between" style="padding:16px 18px 10px"><div class="b fs15">Notifications</div><button type="button" class="link fs12" @click="notif=false">Mark all read</button></div>
          @foreach ($notifications as $n)
            <a href="{{ $n['url'] }}" class="notif">
              <div style="width:36px;height:36px;border-radius:12px;flex:none;display:flex;align-items:center;justify-content:center;background:{{ $n['bg'] }}"><span class="ms" style="font-size:19px;color:{{ $n['color'] }}">{{ $n['icon'] }}</span></div>
              <div class="grow"><div style="font-size:13px;line-height:1.4;color:#E6EEF8">{{ $n['text'] }}</div><div class="faint" style="font-size:11.5px;margin-top:3px">{{ $n['time'] }}</div></div>
              @if ($n['unread'])<span style="width:7px;height:7px;border-radius:50%;background:var(--blue);margin-top:6px;flex:none"></span>@endif
            </a>
          @endforeach
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

    {{-- Global mini player --}}
    <div class="player" x-show="$store.player.item" x-cloak>
      <template x-if="$store.player.item">
        <div class="row grow" style="gap:16px">
          <div class="player-art" :style="`width:${$store.player.thumbW};background:${$store.player.item.art}`"><span class="ms" x-text="$store.player.item.type === 'Films' ? 'movie' : 'graphic_eq'"></span></div>
          <div style="width:200px;min-width:0;flex:none">
            <div class="b ell" style="font-size:14.5px" x-text="$store.player.item.title"></div>
            <div class="ell muted" style="font-size:12.5px"><span x-text="$store.player.item.creator"></span> · <span class="amber" x-text="$store.player.tag"></span></div>
          </div>
          <div class="row g6" style="flex:none">
            <button type="button" class="pbtn" @click="$store.player.skip(-10)"><span class="ms">replay_10</span></button>
            <button type="button" class="pplay" @click="$store.player.toggle()"><span class="ms" x-text="$store.player.playing ? 'pause' : 'play_arrow'"></span></button>
            <button type="button" class="pbtn" @click="$store.player.skip(10)"><span class="ms">forward_10</span></button>
          </div>
          <div class="row grow p-meta-wide" style="min-width:120px;gap:12px">
            <span class="fs12 muted num right" style="width:40px" x-text="$store.player.cur"></span>
            <div class="wave grow" style="height:28px" @click="$store.player.seek($event)">
              <template x-for="(h, i) in $store.player.item.wave" :key="i">
                <i :style="`height:${Math.max(12, h)}%;background:${i / $store.player.item.wave.length < $store.player.pos ? '#0A9BFF' : 'rgba(255,255,255,.16)'}`"></i>
              </template>
            </div>
            <span class="fs12 muted num" style="width:40px" x-text="$store.player.total"></span>
          </div>
          <div class="row g6 p-extra" style="flex:none">
            <a :href="$store.player.item.url" class="btn btn-xs" style="height:36px;border-radius:18px;font-size:12.5px"><span class="ms" style="font-size:17px">open_in_new</span>Open review</a>
            <span class="ms muted" style="padding:0 6px">volume_up</span>
          </div>
          <button type="button" class="pbtn" style="width:32px;height:32px;color:var(--muted)" @click="$store.player.close()"><span class="ms" style="font-size:19px">close</span></button>
        </div>
      </template>
    </div>

    {{-- Toast --}}
    <div class="toast" x-show="$store.toast.on" x-cloak x-transition.opacity>
      <div class="toast-ico" :style="`background:${$store.toast.bg}`"><span class="ms" :style="`color:${$store.toast.fg}`" x-text="$store.toast.icon"></span></div>
      <div style="font-size:13.5px;font-weight:500" x-text="$store.toast.msg"></div>
      <button type="button" class="link" style="margin-left:8px;font-size:12.5px" @click="$store.toast.on=false">Dismiss</button>
    </div>
  </div>

  {{-- Command palette --}}
  <div class="backdrop top-align" x-show="palette" x-cloak @click.self="palette=false" style="padding-top:12vh">
    <div class="palette">
      <div class="palette-in">
        <span class="ms blue" style="font-size:22px">search</span>
        <input x-ref="pq" x-model="q" @input="search()" placeholder="Type a command or search…">
        <span class="kbd">ESC</span>
      </div>
      <div style="max-height:420px;overflow-y:auto;padding:8px">
        <template x-for="grp in results" :key="grp.label">
          <div>
            <div class="nav-label" style="padding:10px 12px 6px" x-text="grp.label"></div>
            <template x-for="r in grp.items" :key="r.url + r.label">
              <a :href="r.url" class="pal-item" :class="flat.indexOf(r) === hi && 'on'" @mouseenter="hi = flat.indexOf(r)">
                <div class="pal-ico" :style="`background:${r.art}`"><span class="ms" x-text="r.icon"></span></div>
                <span class="grow sb fs14" x-text="r.label"></span>
                <span class="fs12 faint" x-text="r.sub"></span>
              </a>
            </template>
          </div>
        </template>
        <div x-show="results.length === 0" class="center muted fs14" style="padding:32px">No matches for “<span x-text="q"></span>”</div>
      </div>
      <div class="row faint fs12" style="gap:18px;padding:10px 20px;border-top:1px solid rgba(255,255,255,.07)"><span>↑↓ navigate</span><span>↵ open</span><span>⌘K toggle</span></div>
    </div>
  </div>
</div>

@if (session('toast'))
<script>document.addEventListener('alpine:initialized', () => Alpine.store('toast').show(@json(session('toast')[0]), @json(session('toast')[1] ?? 'ok')));</script>
@endif
@stack('scripts')
</body>
</html>
