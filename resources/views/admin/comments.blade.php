@extends('layouts.admin')
@section('title', 'Comments')

@section('content')
<div class="page">
  <div class="page-head">
    <div><h1 class="h1">Comments</h1><div class="sub">{{ $content ? 'On “'.$content->title.'”' : 'Everything listeners are saying' }} · {{ $counts['Reported'] ?? 0 }} reported</div></div>
    @if ($content)<a href="{{ route('comments.index') }}" class="btn btn-sm">Show all comments</a>@endif
  </div>
  <form method="GET" class="row wrap">
    @if ($content)<input type="hidden" name="content" value="{{ $content->id }}">@endif
    <input type="hidden" name="status" value="{{ $status }}">
    <label class="searchbox"><span class="ms">search</span><input name="q" value="{{ $q }}" placeholder="Search comment text" x-data @input.debounce.500ms="$el.form.submit()" autocomplete="off"></label>
    <div class="seg sm">
      @foreach (['All', 'Visible', 'Reported', 'Hidden'] as $s)
        <a href="{{ route('comments.index', array_filter(['status' => $s === 'All' ? null : $s, 'q' => $q, 'content' => $content?->id])) }}" class="{{ $status === $s ? 'on' : '' }}">{{ $s }}</a>
      @endforeach
    </div>
  </form>

  <div class="card flush r22">
    @forelse ($comments as $c)
      <div class="row g14 start" style="padding:16px 20px;{{ $loop->first ? '' : 'border-top:1px solid var(--line-soft)' }}">
        <div class="avatar" style="width:40px;height:40px;background:{{ $c->listener->art }};font-size:13px">{{ $c->listener->initials }}</div>
        <div class="grow">
          <div class="row wrap g8 fs13"><b>{{ $c->listener->name }}</b><span class="faint">on</span><a href="{{ route('library.show', $c->content) }}" class="row g6" style="color:var(--text-2)"><span class="thumb" style="width:20px;height:20px;border-radius:5px;background:{{ $c->content->art }}"></span>{{ $c->content->title }}</a><span class="faint">· {{ $c->created_at->diffForHumans() }}</span></div>
          <div style="font-size:14px;line-height:1.5;margin-top:6px;color:{{ $c->status === 'Hidden' ? 'var(--faint)' : '#E6EEF8' }}">{{ $c->body }}</div>
        </div>
        @include('admin.partials.status', ['s' => $c->status])
        <form method="POST" action="{{ route('comments.toggle', $c) }}">@csrf<button class="icon-btn sm" title="{{ $c->status === 'Hidden' ? 'Show' : 'Hide' }}"><span class="ms" style="font-size:19px">{{ $c->status === 'Hidden' ? 'visibility' : 'visibility_off' }}</span></button></form>
        <form method="POST" action="{{ route('comments.destroy', $c) }}" onsubmit="return confirm('Delete this comment permanently?')">@csrf @method('DELETE')<button class="icon-btn sm r" title="Delete"><span class="ms" style="font-size:19px">delete</span></button></form>
      </div>
    @empty
      <div class="center muted" style="padding:48px">No comments match.</div>
    @endforelse
    @include('admin.partials.pager', ['p' => $comments])
  </div>
</div>
@endsection
