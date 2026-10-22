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
      <h1 class="h1 xl">{{ $greet }}, Admin</h1>
      <div class="sub">
        @if ($pendingTotal)
          {{ $pendingTotal }} {{ Str::plural('upload', $pendingTotal) }} waiting for review — the oldest for {{ $oldestWait }}.
        @else
          Nothing waiting for review.
        @endif
      </div>
    </div>
    @if ($pendingTotal)<a href="{{ route('queue.index') }}" class="btn btn-primary"><span class="ms">inventory</span>Start reviewing</a>@endif
  </div>

  <div class="grid-kpi">
    @foreach ($stats as [$icon, $label, $value, $url, $color])
      <a href="{{ $url }}" class="kpi">
        <div class="kpi-label"><span class="ms" style="color:{{ $color }}">{{ $icon }}</span>{{ $label }}</div>
        <div class="kpi-value">{{ number_format($value) }}</div>
      </a>
    @endforeach
  </div>

  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,420px),1fr));gap:16px;align-items:start">
    <div class="card col g14">
      <div class="row between"><div class="card-title">Waiting for review</div><a href="{{ route('queue.index') }}" class="link">Open queue</a></div>
      @forelse ($pending as $q)
        <a href="{{ route('review.show', $q) }}" class="list-row">
          <div class="art" style="width:42px;height:42px;background:{{ $q->art }}"></div>
          <div class="grow"><div class="sb fs14 ell">{{ $q->title }}</div><div class="fs12 muted row g4"><span class="ms" style="font-size:14px">{{ $q->icon }}</span>{{ $q->creator->name }}</div></div>
          <span class="pill {{ $q->urgent ? 'amber' : 'neutral' }}">{{ \App\Support\Iw::waitShort($q->wait_hours) }}</span>
        </a>
      @empty
        <div class="fs13 muted">All caught up.</div>
      @endforelse
    </div>

    <div class="card col g14">
      <div class="row between"><div class="card-title">Open reports</div><a href="{{ route('moderation') }}" class="link">Open moderation</a></div>
      @forelse ($reports as $r)
        <a href="{{ route('moderation') }}" class="list-row">
          <div class="art" style="width:42px;height:42px;background:rgba(249,128,52,.14);display:flex;align-items:center;justify-content:center"><span class="ms orange">flag</span></div>
          <div class="grow"><div class="sb fs14 ell">{{ $r->label }}</div><div class="fs12 muted">{{ $r->reason }}</div></div>
          <span class="fs12 b orange num">{{ $r->count }}×</span>
        </a>
      @empty
        <div class="fs13 muted">No open reports.</div>
      @endforelse
    </div>
  </div>
</div>
@endsection
