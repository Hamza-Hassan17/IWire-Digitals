@extends('layouts.admin')
@section('title', 'Dashboard')

@php
    $hour = now()->hour;
    $greet = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
@endphp

@section('content')
<div class="page" style="gap:24px">
  <div class="page-head">
    <div>
      <div class="fs13 muted" style="font-weight:500">{{ now()->format('l, F j') }} · {{ $ctx === 'All' ? 'All of iWire' : 'iWire '.$ctx }}</div>
      <h1 class="h1 xl" style="margin-top:6px">{{ $greet }}, Admin</h1>
      <div class="sub">
        @if ($pending->count())
          {{ $pending->count() }} uploads are waiting for review — the oldest has been in the queue for {{ \App\Support\Iw::waitShort($oldestWait) }}.
        @else
          The review queue is clear. Nice work.
        @endif
      </div>
    </div>
    <div class="row">
      <a href="{{ route('analytics') }}" class="btn"><span class="ms">monitoring</span>Full analytics</a>
      <a href="{{ route('queue.index') }}" class="btn btn-primary"><span class="ms">inventory</span>Start reviewing</a>
    </div>
  </div>

  <div class="grid-kpi">
    @foreach ($kpis as $c)
      <a href="{{ $c['url'] }}" class="kpi">
        <div class="kpi-label"><span class="ms" style="color:{{ $c['iconColor'] }}">{{ $c['icon'] }}</span>{{ $c['label'] }}</div>
        <div class="row" style="align-items:baseline"><div class="kpi-value">{{ $c['value'] }}</div><div class="kpi-delta" style="color:{{ $c['deltaColor'] }}">{{ $c['delta'] }}</div></div>
        <svg viewBox="0 0 100 32" preserveAspectRatio="none">
          <path d="{{ $c['area'] }}" fill="{{ $c['fill'] }}"></path>
          <path d="{{ $c['line'] }}" fill="none" stroke="{{ $c['stroke'] }}" stroke-width="1.6" vector-effect="non-scaling-stroke" stroke-linejoin="round"></path>
        </svg>
      </a>
    @endforeach
  </div>

  <div class="split">
    <div class="card">
      <div class="row between wrap g12">
        <div><div class="card-title">Streams over time</div><div class="card-sub">Daily plays, last 30 days</div></div>
        <div class="row" style="gap:18px">
          @if ($showMusic)<div class="legend"><i style="background:#0A9BFF"></i>Music <b class="num">{{ $chart['mTotal'] }}</b></div>@endif
          @if ($showFilms)<div class="legend"><i style="background:#9FD8FF"></i>Films <b class="num">{{ $chart['fTotal'] }}</b></div>@endif
          <div class="row" style="gap:2px;padding:3px;border-radius:16px;background:rgba(255,255,255,.05)">
            <span class="fs12 sb muted" style="padding:5px 10px">7D</span><span class="fs12 sb" style="padding:5px 10px;border-radius:13px;background:rgba(10,155,255,.2)">30D</span><span class="fs12 sb muted" style="padding:5px 10px">90D</span>
          </div>
        </div>
      </div>
      <div style="position:relative;margin-top:18px;height:250px">
        <div class="chart-grid"><div></div><div></div><div></div><div></div><div></div></div>
        <div class="num faint" style="position:absolute;left:0;top:-7px;bottom:17px;display:flex;flex-direction:column;justify-content:space-between;font-size:11px"><span>160K</span><span>120K</span><span>80K</span><span>40K</span><span>0</span></div>
        <svg viewBox="0 0 600 226" preserveAspectRatio="none" style="position:absolute;left:40px;top:0;width:calc(100% - 40px);height:226px">
          <defs>
            <linearGradient id="dgM" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#0A9BFF" stop-opacity=".35"/><stop offset="1" stop-color="#0A9BFF" stop-opacity="0"/></linearGradient>
            <linearGradient id="dgF" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#9FD8FF" stop-opacity=".18"/><stop offset="1" stop-color="#9FD8FF" stop-opacity="0"/></linearGradient>
          </defs>
          @if ($showFilms)<path d="{{ $chart['fArea'] }}" fill="url(#dgF)"/><path d="{{ $chart['fLine'] }}" fill="none" stroke="#9FD8FF" stroke-width="2" vector-effect="non-scaling-stroke" stroke-linejoin="round"/>@endif
          @if ($showMusic)<path d="{{ $chart['mArea'] }}" fill="url(#dgM)"/><path d="{{ $chart['mLine'] }}" fill="none" stroke="#0A9BFF" stroke-width="2.5" vector-effect="non-scaling-stroke" stroke-linejoin="round"/>@endif
        </svg>
        <div class="row between faint" style="position:absolute;left:40px;right:0;bottom:0;font-size:11px">@foreach ($chart['labels'] as $l)<span>{{ $l }}</span>@endforeach</div>
      </div>
    </div>

    <div class="card col" style="padding:22px 20px;gap:14px">
      <div class="row between"><div class="card-title">Needs attention</div><span class="pill amber" style="font-weight:700">{{ $attention }} items</span></div>
      <div class="label-xs">Oldest pending</div>
      @forelse ($oldest as $q)
        <a href="{{ route('review.show', $q) }}" class="list-row">
          <div class="art" style="width:42px;height:42px;background:{{ $q->art }}"></div>
          <div class="grow"><div class="sb fs14 ell">{{ $q->title }}</div><div class="fs12 muted row g4"><span class="ms" style="font-size:14px">{{ $q->icon }}</span>{{ $q->creator->name }}</div></div>
          <span class="pill amber" style="font-size:11.5px;padding:4px 9px">{{ \App\Support\Iw::waitShort($q->wait_hours) }}</span>
        </a>
      @empty
        <div class="fs13 muted">Nothing pending.</div>
      @endforelse
      <div class="label-xs mt6">Open reports</div>
      @forelse ($openReports as $r)
        <a href="{{ route('reports.index') }}" class="list-row">
          <div class="art" style="width:42px;height:42px;background:rgba(249,128,52,.14);display:flex;align-items:center;justify-content:center"><span class="ms orange">flag</span></div>
          <div class="grow"><div class="sb fs14 ell">{{ $r->label }}</div><div class="fs12 muted">{{ $r->reason }}</div></div>
          <span class="fs12 b orange num">{{ $r->count }}×</span>
        </a>
      @empty
        <div class="fs13 muted">No open reports.</div>
      @endforelse
    </div>
  </div>

  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,520px),1fr));gap:16px">
    @if ($showMusic)
      <div class="card">
        <div class="row between" style="margin-bottom:16px"><div class="row card-title"><span class="ms blue" style="font-size:22px">graphic_eq</span>Top tracks this week</div><a href="{{ route('library.index', ['tab' => 'Music']) }}" class="link">See all</a></div>
        <div class="top5">
          @foreach ($topTracks as $i => $t)
            <div class="col g8 scale" @click="$store.player.play(@js($t->toPlayer()))">
              <div class="cover" style="aspect-ratio:1;background:{{ $t->art }}">
                <span class="rank">{{ $i + 1 }}</span>
                <span class="playdot" style="position:absolute;right:8px;bottom:8px;width:32px;height:32px"><span class="ms" style="font-size:20px">play_arrow</span></span>
              </div>
              <div style="min-width:0"><div class="sb ell" style="font-size:13.5px">{{ $t->title }}</div><div class="fs12 muted ell">{{ $t->creator->name }} · {{ $t->plays_k }}</div></div>
            </div>
          @endforeach
        </div>
      </div>
    @endif
    @if ($showFilms)
      <div class="card">
        <div class="row between" style="margin-bottom:16px"><div class="row card-title"><span class="ms blue" style="font-size:22px">movie</span>Top films this week</div><a href="{{ route('library.index', ['tab' => 'Films']) }}" class="link">See all</a></div>
        <div class="top5">
          @foreach ($topFilms as $i => $t)
            <div class="col g8 scale" @click="$store.player.play(@js($t->toPlayer()))">
              <div class="cover" style="aspect-ratio:2/3;max-height:150px;border-radius:14px;background:{{ $t->art }}">
                <span class="rank">{{ $i + 1 }}</span>
                <span class="playdot" style="position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);width:34px;height:34px;background:rgba(5,11,22,.7)"><span class="ms" style="font-size:20px">play_arrow</span></span>
                <div class="progress-bar"><div style="width:{{ $t->progress }}%"></div></div>
              </div>
              <div style="min-width:0"><div class="sb ell" style="font-size:13.5px">{{ $t->title }}</div><div class="fs12 muted ell">{{ $t->genre }} · {{ $t->plays_k }}</div></div>
            </div>
          @endforeach
        </div>
      </div>
    @endif
  </div>

  <div class="card">
    <div class="row between" style="margin-bottom:8px"><div class="card-title">Recent activity</div><a href="{{ route('activity') }}" class="link">View log</a></div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));column-gap:32px">
      @foreach ($activity as $a)
        @include('admin.partials.activity-row', ['a' => $a])
      @endforeach
    </div>
  </div>
</div>
@endsection
