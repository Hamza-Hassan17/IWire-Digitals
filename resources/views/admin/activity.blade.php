@extends('layouts.admin')
@section('title', 'Activity Log')

@section('content')
<div class="page">
  <div class="page-head"><div><h1 class="h1">Activity Log</h1><div class="sub">Every moderation and publishing action, newest first{{ $ctx !== 'All' ? ' · '.$ctx : '' }}.</div></div></div>
  <div class="card flush r22">
    <div style="padding:6px 24px">
      @forelse ($items as $a)
        @include('admin.partials.activity-row', ['a' => $a])
      @empty
        <div class="center muted" style="padding:40px">No activity yet.</div>
      @endforelse
    </div>
    @include('admin.partials.pager', ['p' => $items])
  </div>
</div>
@endsection
