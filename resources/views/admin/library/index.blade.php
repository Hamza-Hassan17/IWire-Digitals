@extends('layouts.admin')
@section('title', 'Library')

@php
    $films = $tab === 'Films';
    $url = fn (array $over) => route('library.index', array_merge(['tab' => $tab, 'view' => $view], array_filter($f, fn ($v) => ! str_starts_with($v, 'Any') && $v !== ''), $over));
@endphp

@section('content')
<div class="page">
  <div class="page-head">
    <div><h1 class="h1">Library</h1><div class="sub">{{ $countLabel }}</div></div>
    <div class="seg">
      <a href="{{ route('library.index', ['tab' => 'Music', 'view' => $view]) }}" class="{{ ! $films ? 'on' : '' }}" style="height:38px;padding:0 18px;font-size:14px"><span class="ms" style="font-size:19px">graphic_eq</span>Music</a>
      <a href="{{ route('library.index', ['tab' => 'Films', 'view' => $view]) }}" class="{{ $films ? 'on' : '' }}" style="height:38px;padding:0 18px;font-size:14px"><span class="ms" style="font-size:19px">movie</span>Films</a>
    </div>
  </div>

  <form method="GET" action="{{ route('library.index') }}" class="row wrap">
    <input type="hidden" name="tab" value="{{ $tab }}"><input type="hidden" name="view" value="{{ $view }}">
    <label class="searchbox"><span class="ms">search</span><input name="q" value="{{ $f['q'] }}" placeholder="Search title or creator" x-data @input.debounce.500ms="$el.form.submit()" autocomplete="off"></label>
    @foreach ($filters as [$icon, $key, $opts])
      <label class="filter {{ $f[$key] !== $opts[0] ? 'on' : '' }}"><span class="ms">{{ $icon }}</span>
        <select name="{{ $key }}" onchange="this.form.submit()">@foreach ($opts as $o)<option @selected($f[$key] === $o)>{{ $o }}</option>@endforeach</select>
      </label>
    @endforeach
    <div class="grow"></div>
    <div class="viewtoggle">
      <a href="{{ $url(['view' => 'grid']) }}" class="{{ $view === 'grid' ? 'on' : '' }}"><span class="ms">grid_view</span></a>
      <a href="{{ $url(['view' => 'table']) }}" class="{{ $view === 'table' ? 'on' : '' }}"><span class="ms">view_list</span></a>
    </div>
  </form>

  @if ($items->isEmpty())
    <div class="empty"><span class="ms faint" style="font-size:40px">search_off</span><div class="b mt8" style="font-size:18px">No matches</div><div class="fs14 muted mt4">Try a different search or <a href="{{ route('library.index', ['tab' => $tab]) }}">clear filters</a>.</div></div>
  @elseif ($view === 'grid')
    <div class="tile-grid" style="grid-template-columns:repeat(auto-fill,minmax({{ $films ? '170px' : '190px' }},1fr))">
      @foreach ($items as $it)
        @php $st = \App\Support\Iw::st($it->status); @endphp
        <a href="{{ route('library.show', $it) }}" class="col scale" style="gap:10px">
          <div class="cover" style="aspect-ratio:{{ $films ? '2/3' : '1' }};border-radius:18px;background:{{ $it->art }};box-shadow:0 14px 30px rgba(0,0,0,.35)">
            <span class="overlay-pill" style="position:absolute;top:10px;left:10px;font-size:11px;color:{{ $st['fg'] }};gap:5px"><span style="width:6px;height:6px;border-radius:50%;background:{{ $st['dot'] }}"></span>{{ $it->status }}</span>
            <button type="button" class="playdot" style="position:absolute;right:10px;bottom:12px" @click.prevent.stop="$store.player.play(@js($it->toPlayer()))"><span class="ms">play_arrow</span></button>
            @if ($films)<div class="progress-bar" style="height:4px;background:rgba(255,255,255,.18)"><div style="width:{{ $it->progress }}%"></div></div>@endif
          </div>
          <div style="min-width:0">
            <div class="b ell fs15">{{ $it->title }}</div>
            <div class="fs13 muted ell">{{ $it->creator->name }} · {{ $it->genre }}</div>
            <div class="fs12 faint row g4" style="margin-top:3px"><span class="ms" style="font-size:14px">play_arrow</span>{{ $it->plays_k }} plays</div>
          </div>
        </a>
      @endforeach
    </div>
  @else
    <div class="card flush r22 table-wrap" style="max-height:680px">
      <table class="tbl" style="min-width:860px">
        <thead><tr><th>Title</th><th>Genre</th><th>Status</th><th class="right">Plays</th><th class="right">Shares</th><th class="right">Duration</th><th>Uploaded</th></tr></thead>
        <tbody>
          @foreach ($items as $it)
            <tr class="click" onclick="window.location='{{ route('library.show', $it) }}'">
              <td><div class="row g12"><div class="thumb" style="width:{{ $films ? '32px' : '44px' }};background:{{ $it->art }}"></div><div><div class="sb">{{ $it->title }}</div><div class="muted" style="font-size:12.5px">{{ $it->creator->name }}</div></div></div></td>
              <td class="t2">{{ $it->genre }}</td>
              <td>@include('admin.partials.status', ['s' => $it->status])</td>
              <td class="right num sb">{{ $it->plays_k }}</td>
              <td class="right num t2">{{ \App\Support\Iw::k($it->shares) }}</td>
              <td class="right num t2">{{ $it->duration }}</td>
              <td class="t3">{{ $it->submitted_at?->format('M j') }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>
@endsection
