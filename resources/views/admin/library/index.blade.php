@extends('layouts.admin')
@section('title', 'Library')

@php $films = $tab === 'Films'; @endphp

@section('content')
<div class="page">
  <div class="page-head">
    <div><h1 class="h1">Library</h1><div class="sub">{{ $countLabel }}</div></div>
    <div class="seg">
      <a href="{{ route('library.index', ['tab' => 'Music']) }}" class="{{ ! $films ? 'on' : '' }}"><span class="ms">graphic_eq</span>Music</a>
      <a href="{{ route('library.index', ['tab' => 'Films']) }}" class="{{ $films ? 'on' : '' }}"><span class="ms">movie</span>Films</a>
    </div>
  </div>

  <form method="GET" action="{{ route('library.index') }}" class="row wrap">
    <input type="hidden" name="tab" value="{{ $tab }}">
    <label class="searchbox"><span class="ms">search</span><input name="q" value="{{ $q }}" placeholder="Search title or creator" x-data @input.debounce.500ms="$el.form.submit()" autocomplete="off"></label>
    <label class="filter {{ $status !== 'Any status' ? 'on' : '' }}"><span class="ms">radio_button_checked</span>
      <select name="status" onchange="this.form.submit()">@foreach ($statuses as $o)<option @selected($status === $o)>{{ $o }}</option>@endforeach</select>
    </label>
  </form>

  @if ($items->isEmpty())
    <div class="empty"><span class="ms faint" style="font-size:40px">search_off</span><div class="b mt8" style="font-size:18px">No matches</div><div class="fs14 muted mt4">Try a different search or <a href="{{ route('library.index', ['tab' => $tab]) }}">clear filters</a>.</div></div>
  @else
    <div class="tile-grid" style="grid-template-columns:repeat(auto-fill,minmax({{ $films ? '170px' : '190px' }},1fr))">
      @foreach ($items as $it)
        @php $st = \App\Support\Iw::st($it->status); @endphp
        <a href="{{ route('library.show', $it) }}" class="col scale" style="gap:10px">
          <div class="cover" style="aspect-ratio:{{ $films ? '2/3' : '1' }};border-radius:18px;background:{{ $it->art }}">
            <span class="overlay-pill" style="position:absolute;top:10px;left:10px;font-size:11px;color:{{ $st['fg'] }};gap:5px"><span style="width:6px;height:6px;border-radius:50%;background:{{ $st['dot'] }}"></span>{{ $it->status }}</span>
            @unless ($it->file_path)<span class="overlay-pill amber" style="position:absolute;left:10px;bottom:10px;font-size:11px" title="No media file uploaded — the app can't play this"><span class="ms" style="font-size:14px">warning</span>No file</span>@endunless
          </div>
          <div style="min-width:0">
            <div class="b ell fs15">{{ $it->title }}</div>
            <div class="fs13 muted ell">{{ $it->creator->name }} · {{ $it->genre }}</div>
          </div>
        </a>
      @endforeach
    </div>
  @endif
</div>
@endsection
