@extends('layouts.admin')
@section('title', 'Reports & Flags')

@section('content')
<div class="page">
  <div class="page-head">
    <div><h1 class="h1">Reports & Flags</h1><div class="sub">{{ $counts['Open'] ?? 0 }} open reports from listeners. Resolving takes the item down; dismissing restores it.</div></div>
  </div>
  <div class="seg">
    @foreach (['Open', 'Resolved', 'Dismissed', 'All'] as $s)
      <a href="{{ route('reports.index', ['status' => $s]) }}" class="{{ $status === $s ? 'on' : '' }}">{{ $s }}<span class="count">{{ $s === 'All' ? $counts->sum() : ($counts[$s] ?? 0) }}</span></a>
    @endforeach
  </div>

  @if ($reports->isEmpty())
    <div class="empty"><span class="ms green" style="font-size:40px">verified</span><div class="b mt8" style="font-size:18px">No {{ strtolower($status) }} reports</div><div class="fs14 muted mt4">Nothing needs your attention here.</div></div>
  @else
    <div class="card flush r22 table-wrap">
      <table class="tbl" style="min-width:900px">
        <thead><tr><th>Reported item</th><th>Kind</th><th>Reason</th><th class="right">Reports</th><th>Date</th><th>Status</th><th class="right">Actions</th></tr></thead>
        <tbody>
          @foreach ($reports as $r)
            <tr>
              <td>
                @php $link = $r->content ? route('library.show', $r->content) : ($r->comment ? route('comments.index', ['status' => 'Reported']) : null); @endphp
                <a @if ($link) href="{{ $link }}" @endif class="row g12" style="color:#fff">
                  <div class="thumb" style="width:44px;background:{{ $r->art }};display:flex;align-items:center;justify-content:center">@if ($r->kind === 'Comment')<span class="ms" style="font-size:18px">chat_bubble</span>@endif</div>
                  <div><div class="sb">{{ $r->label }}</div><div class="muted" style="font-size:12.5px">{{ $r->subject_name }}</div></div>
                </a>
              </td>
              <td><span class="row g4 sb" style="color:var(--blue-text);font-size:12.5px"><span class="ms" style="font-size:16px">{{ $r->icon }}</span>{{ $r->kind }}</span></td>
              <td class="t2">{{ $r->reason }}</td>
              <td class="right num b orange">{{ $r->count }}×</td>
              <td class="t3 nowrap">{{ $r->created_at->format('M j') }}</td>
              <td>@include('admin.partials.status', ['s' => $r->status])</td>
              <td><div class="row g6 end">
                @if ($r->status === 'Open')
                  <form method="POST" action="{{ route('reports.dismiss', $r) }}">@csrf<button class="btn btn-sm btn-text">Dismiss</button></form>
                  <form method="POST" action="{{ route('reports.resolve', $r) }}">@csrf<button class="btn btn-sm btn-danger"><span class="ms" style="font-size:17px">gavel</span>{{ $r->kind === 'Comment' ? 'Hide comment' : 'Take down' }}</button></form>
                @else
                  <span class="fs12 faint">{{ $r->updated_at->diffForHumans() }}</span>
                @endif
              </div></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>
@endsection
