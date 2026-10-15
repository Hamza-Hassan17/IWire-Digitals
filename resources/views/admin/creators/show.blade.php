@extends('layouts.admin')
@section('title', $cr->name)

@section('content')
@php $sus = $cr->status === 'Suspended'; @endphp
<div class="page">
  <a href="{{ route('creators.index') }}" class="back"><span class="ms" style="font-size:19px">arrow_back</span>Creators</a>
  <div class="hero">
    <div class="hero-bg" style="background:{{ $cr->art }}"></div>
    <div class="hero-shade side"></div>
    <div class="hero-body">
      <div class="avatar" style="width:120px;height:120px;background:{{ $cr->art }};font-size:40px;font-weight:800;border:3px solid rgba(255,255,255,.15)">{{ $cr->initials }}</div>
      <div class="grow" style="min-width:260px">
        @include('admin.partials.status', ['s' => $cr->status])
        <h1 style="margin:12px 0 8px;font-size:44px;font-weight:800;letter-spacing:-.03em;line-height:1">{{ $cr->name }}</h1>
        <div class="t2" style="font-size:14.5px">{{ $cr->type }} · {{ $cr->email }} · Joined {{ $cr->joined_at?->format('M Y') }}</div>
      </div>
      <form method="POST" action="{{ route('creators.suspend', $cr) }}">@csrf
        <button class="btn btn-md {{ $sus ? '' : 'btn-danger' }}"><span class="ms">{{ $sus ? 'restart_alt' : 'block' }}</span>{{ $sus ? 'Reactivate' : 'Suspend creator' }}</button>
      </form>
    </div>
  </div>

  <div class="grid-kpi">
    @foreach ([['upload', 'Uploads', $stats['uploads']], ['check_circle', 'Approved', $stats['approved']], ['cancel', 'Rejected', $stats['rejected']], ['play_circle', 'Total plays', \App\Support\Iw::k($cr->total_plays)], ['percent', 'Approval rate', $stats['rate'].'%']] as [$i, $l, $v])
      <div class="kpi"><div class="kpi-label"><span class="ms">{{ $i }}</span>{{ $l }}</div><div class="kpi-value">{{ $v }}</div></div>
    @endforeach
  </div>

  <div class="card flush r22 table-wrap">
    <table class="tbl" style="min-width:760px">
      <thead><tr><th>Upload</th><th>Genre</th><th>Status</th><th class="right">Plays</th><th class="right">Duration</th><th>Submitted</th></tr></thead>
      <tbody>
        @forelse ($cr->contents as $it)
          <tr class="click" onclick="window.location='{{ $it->status === 'Pending' ? route('review.show', $it) : route('library.show', $it) }}'">
            <td><div class="row g12"><div class="thumb" style="width:{{ $it->is_music ? '44px' : '70px' }};background:{{ $it->art }}"></div><div><div class="sb">{{ $it->title }}</div><div class="muted row g4" style="font-size:12.5px"><span class="ms" style="font-size:14px">{{ $it->icon }}</span>{{ $it->type }}</div></div></div></td>
            <td class="t2">{{ $it->genre }}</td>
            <td>@include('admin.partials.status', ['s' => $it->status])</td>
            <td class="right num sb">{{ $it->plays_k }}</td>
            <td class="right num t2">{{ $it->duration }}</td>
            <td class="t3">{{ $it->submitted_at?->format('M j, Y') }}</td>
          </tr>
        @empty
          <tr><td colspan="6" class="center muted" style="padding:40px">No uploads yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
