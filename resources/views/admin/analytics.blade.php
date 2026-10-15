@extends('layouts.admin')
@section('title', 'Analytics')

@section('content')
<div class="page">
  <div class="page-head"><div><h1 class="h1">Analytics</h1><div class="sub">{{ $ctx === 'All' ? 'All of iWire' : 'iWire '.$ctx }} · last 90 days</div></div></div>

  <div class="grid-kpi">
    @foreach ($totals as [$i, $l, $v])
      <div class="kpi"><div class="kpi-label"><span class="ms">{{ $i }}</span>{{ $l }}</div><div class="kpi-value">{{ $v }}</div></div>
    @endforeach
  </div>

  <div class="card">
    <div class="row between wrap g12">
      <div><div class="card-title">Daily streams</div><div class="card-sub">Music vs films</div></div>
      <div class="row" style="gap:18px">
        @if ($ctx !== 'Films')<div class="legend"><i style="background:#0A9BFF"></i>Music</div>@endif
        @if ($ctx !== 'Music')<div class="legend"><i style="background:#9FD8FF"></i>Films</div>@endif
      </div>
    </div>
    <div style="position:relative;margin-top:18px;height:250px">
      <div class="chart-grid"><div></div><div></div><div></div><div></div><div></div></div>
      <div class="num faint" style="position:absolute;left:0;top:-7px;bottom:17px;display:flex;flex-direction:column;justify-content:space-between;font-size:11px"><span>160K</span><span>120K</span><span>80K</span><span>40K</span><span>0</span></div>
      <svg viewBox="0 0 600 226" preserveAspectRatio="none" style="position:absolute;left:40px;top:0;width:calc(100% - 40px);height:226px">
        <defs>
          <linearGradient id="aM" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#0A9BFF" stop-opacity=".35"/><stop offset="1" stop-color="#0A9BFF" stop-opacity="0"/></linearGradient>
          <linearGradient id="aF" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#9FD8FF" stop-opacity=".18"/><stop offset="1" stop-color="#9FD8FF" stop-opacity="0"/></linearGradient>
        </defs>
        @if ($ctx !== 'Music')<path d="{{ $fArea }}" fill="url(#aF)"/><path d="{{ $fLine }}" fill="none" stroke="#9FD8FF" stroke-width="2" vector-effect="non-scaling-stroke"/>@endif
        @if ($ctx !== 'Films')<path d="{{ $mArea }}" fill="url(#aM)"/><path d="{{ $mLine }}" fill="none" stroke="#0A9BFF" stroke-width="2.5" vector-effect="non-scaling-stroke"/>@endif
      </svg>
      <div class="row between faint" style="position:absolute;left:40px;right:0;bottom:0;font-size:11px">@foreach ($labels as $l)<span>{{ $l }}</span>@endforeach</div>
    </div>
  </div>

  <div class="split">
    <div class="card">
      <div class="card-title" style="margin-bottom:14px">Plays by genre</div>
      @foreach ($byGenre as $g)
        <div class="row g12" style="padding:7px 0">
          <span class="ms muted" style="font-size:16px">{{ $g->type === 'Music' ? 'graphic_eq' : 'movie' }}</span>
          <span class="sb fs13" style="width:110px">{{ $g->genre }}</span>
          <div class="bar grow" style="height:10px;border-radius:5px"><div style="width:{{ round($g->plays / $topGenre * 100) }}%;border-radius:5px;background:{{ $colors[$g->genre] ?? '#0A9BFF' }}"></div></div>
          <span class="num sb fs13 right" style="width:60px">{{ \App\Support\Iw::k($g->plays) }}</span>
        </div>
      @endforeach
    </div>
    <div class="card tight">
      <div class="card-title" style="margin-bottom:14px">Catalogue status</div>
      @foreach ($status as $s => $n)
        <div class="row between" style="padding:9px 0;border-top:1px solid var(--line-soft)">@include('admin.partials.status', ['s' => $s])<span class="num b">{{ $n }}</span></div>
      @endforeach
    </div>
  </div>

  <div class="card flush r22 table-wrap">
    <table class="tbl" style="min-width:760px">
      <thead><tr><th>#</th><th>Title</th><th>Type</th><th class="right">Plays</th><th class="right">Shares</th><th class="right">Comments</th></tr></thead>
      <tbody>
        @foreach ($top as $i => $t)
          <tr class="click" onclick="window.location='{{ route('library.show', $t) }}'">
            <td class="num faint b">{{ $i + 1 }}</td>
            <td><div class="row g12"><div class="thumb" style="width:{{ $t->is_music ? '40px' : '28px' }};height:40px;background:{{ $t->art }}"></div><div><div class="sb">{{ $t->title }}</div><div class="muted" style="font-size:12.5px">{{ $t->creator->name }}</div></div></div></td>
            <td class="t2">{{ $t->type }}</td>
            <td class="right num sb">{{ $t->plays_k }}</td>
            <td class="right num t2">{{ \App\Support\Iw::k($t->shares) }}</td>
            <td class="right num t2">{{ \App\Support\Iw::k($t->comments_count) }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endsection
