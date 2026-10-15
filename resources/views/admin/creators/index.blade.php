@extends('layouts.admin')
@section('title', 'Creators')

@section('content')
<div class="page">
  <div class="page-head">
    <div><h1 class="h1">Creators</h1><div class="sub">{{ $creators->count() }} {{ $ctx === 'All' ? 'musicians & filmmakers' : ($ctx === 'Music' ? 'musicians' : 'filmmakers') }}</div></div>
  </div>
  <form method="GET" class="row">
    <label class="searchbox"><span class="ms">search</span><input name="q" value="{{ $q }}" placeholder="Search name or email" x-data @input.debounce.500ms="$el.form.submit()" autocomplete="off"></label>
  </form>
  <div class="card flush r22 table-wrap">
    <table class="tbl" style="min-width:860px">
      <thead><tr><th>Creator</th><th>Type</th><th class="right">Uploads</th><th class="right">Total plays</th><th>Approval rate</th><th>Joined</th><th>Status</th></tr></thead>
      <tbody>
        @forelse ($creators as $c)
          @php $s = $c->stats; @endphp
          <tr class="click" onclick="window.location='{{ route('creators.show', $c) }}'">
            <td><div class="row g12"><div class="avatar" style="width:36px;height:36px;background:{{ $c->art }};font-size:12px">{{ $c->initials }}</div><div><div class="sb">{{ $c->name }}</div><div class="muted" style="font-size:12.5px">{{ $c->email }}</div></div></div></td>
            <td><span class="row g4 sb" style="color:var(--blue-text);font-size:12.5px"><span class="ms" style="font-size:16px">{{ $c->icon }}</span>{{ $c->type }}</span></td>
            <td class="right num">{{ $c->contents_count }}</td>
            <td class="right num sb">{{ \App\Support\Iw::k($c->total_plays) }}</td>
            <td style="min-width:140px"><div class="row g8"><div class="bar grow"><div style="width:{{ $s['rate'] }}%"></div></div><span class="num fs12">{{ $s['rate'] }}%</span></div></td>
            <td class="t3">{{ $c->joined_at?->format('M Y') }}</td>
            <td>@include('admin.partials.status', ['s' => $c->status])</td>
          </tr>
        @empty
          <tr><td colspan="7" class="center muted" style="padding:40px">No creators match.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
