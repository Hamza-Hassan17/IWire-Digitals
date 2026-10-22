@extends('layouts.admin')
@section('title', 'Moderation')

@section('content')
<div class="page">
  <div class="page-head">
    <div><h1 class="h1">Moderation</h1><div class="sub">Reports from listeners and comments posted in the app.</div></div>
  </div>
  <div class="seg">
    <a href="{{ route('moderation', ['tab' => 'reports']) }}" class="{{ $tab === 'reports' ? 'on' : '' }}"><span class="ms">flag</span>Reports<span class="count">{{ $openReports }}</span></a>
    <a href="{{ route('moderation', ['tab' => 'comments']) }}" class="{{ $tab === 'comments' ? 'on' : '' }}"><span class="ms">forum</span>Comments<span class="count">{{ $reportedComments }}</span></a>
  </div>

  @if ($tab === 'reports')
    <div class="seg sm">
      @foreach (['Open', 'Closed'] as $s)
        <a href="{{ route('moderation', ['tab' => 'reports', 'status' => $s]) }}" class="{{ $status === $s ? 'on' : '' }}">{{ $s }}</a>
      @endforeach
    </div>
    @if ($reports->isEmpty())
      <div class="empty"><span class="ms green" style="font-size:40px">verified</span><div class="b mt8" style="font-size:18px">No {{ strtolower($status) }} reports</div><div class="fs14 muted mt4">Nothing needs your attention here.</div></div>
    @else
      <div class="card flush r22">
        @foreach ($reports as $r)
          @php $link = $r->content ? route('library.show', $r->content) : null; @endphp
          <div class="row g14" style="padding:14px 20px;{{ $loop->first ? '' : 'border-top:1px solid var(--line-soft)' }}">
            <a @if ($link) href="{{ $link }}" @endif class="thumb" style="width:44px;background:{{ $r->art }};display:flex;align-items:center;justify-content:center;color:#fff">@if ($r->kind === 'Comment')<span class="ms" style="font-size:18px">chat_bubble</span>@endif</a>
            <div class="grow">
              <div class="sb">{{ $r->label }}</div>
              <div class="muted" style="font-size:12.5px">{{ $r->kind }} · {{ $r->subject_name }} · <span class="orange sb">{{ $r->reason }}</span> · reported {{ $r->count }}×</div>
            </div>
            @if ($r->status === 'Open')
              <form method="POST" action="{{ route('reports.dismiss', $r) }}">@csrf<button class="btn btn-sm btn-text">Dismiss</button></form>
              <form method="POST" action="{{ route('reports.resolve', $r) }}">@csrf<button class="btn btn-sm btn-danger">{{ $r->kind === 'Comment' ? 'Hide comment' : 'Take down' }}</button></form>
            @else
              @include('admin.partials.status', ['s' => $r->status])
            @endif
          </div>
        @endforeach
      </div>
    @endif
  @else
    <div class="row wrap">
      <div class="seg sm">
        @foreach (['All', 'Reported', 'Hidden'] as $s)
          <a href="{{ route('moderation', array_filter(['tab' => 'comments', 'status' => $s === 'All' ? null : $s, 'content' => $content?->id])) }}" class="{{ $status === $s ? 'on' : '' }}">{{ $s }}</a>
        @endforeach
      </div>
      @if ($content)<span class="fs13 muted">On “{{ $content->title }}” · <a href="{{ route('moderation', ['tab' => 'comments']) }}">show all</a></span>@endif
    </div>
    <div class="card flush r22">
      @forelse ($comments as $c)
        <div class="row g14 start" style="padding:16px 20px;{{ $loop->first ? '' : 'border-top:1px solid var(--line-soft)' }}">
          <div class="avatar" style="width:40px;height:40px;background:{{ $c->author_art }};font-size:13px">{{ $c->author_initials }}</div>
          <div class="grow">
            <div class="row wrap g8 fs13"><b>{{ $c->author_name }}</b><span class="faint">on</span><a href="{{ route('library.show', $c->content) }}" style="color:var(--text-2)">{{ $c->content->title }}</a><span class="faint">· {{ $c->created_at->diffForHumans() }}</span></div>
            <div style="font-size:14px;line-height:1.5;margin-top:6px;color:{{ $c->status === 'Hidden' ? 'var(--faint)' : '#E6EEF8' }}">{{ $c->body }}</div>
          </div>
          @if ($c->status !== 'Visible')@include('admin.partials.status', ['s' => $c->status])@endif
          <form method="POST" action="{{ route('comments.toggle', $c) }}">@csrf<button class="btn btn-sm">{{ $c->status === 'Hidden' ? 'Show' : 'Hide' }}</button></form>
          <form method="POST" action="{{ route('comments.destroy', $c) }}" onsubmit="return confirm('Delete this comment permanently?')">@csrf @method('DELETE')<button class="icon-btn sm r" title="Delete"><span class="ms" style="font-size:19px">delete</span></button></form>
        </div>
      @empty
        <div class="center muted" style="padding:48px">No comments here.</div>
      @endforelse
      @include('admin.partials.pager', ['p' => $comments])
    </div>
  @endif
</div>
@endsection
