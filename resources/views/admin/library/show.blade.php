@extends('layouts.admin')
@section('title', $d->title)

@php
    use App\Support\Iw;
    $live = $d->status === 'Live';
    $films = ! $d->is_music;
    $stats = [
        ['play_circle', 'Plays', $d->plays_k, '+8.2% vs last week'],
        ['share', 'Shares', Iw::k($d->shares), '+3.1%'],
        ['chat_bubble', 'Comments', Iw::k($d->comments_count), '+44 this week'],
        [$films ? 'timelapse' : 'repeat', $films ? 'Avg. completion' : 'Repeat listens', $films ? $d->progress.'%' : '38%', '+1.4 pts'],
    ];
@endphp

@section('content')
<div class="page" x-data="{ del: false, explicit: {{ $d->explicit ? 'true' : 'false' }} }" @keydown.escape.window="del=false">
  <a href="{{ route('library.index', ['tab' => $d->type]) }}" class="back"><span class="ms" style="font-size:19px">arrow_back</span>Library</a>

  <div class="hero">
    <div class="hero-bg" style="background:{{ $d->art }};opacity:.7;filter:blur(60px)"></div>
    <div class="hero-shade side"></div>
    <div class="hero-body" style="padding:30px">
      <div class="hero-cover" style="width:{{ $films ? '134px' : '200px' }};height:200px;background:{{ $d->art }}">
        <form method="POST" action="{{ route('library.cover', $d) }}" style="position:absolute;left:10px;bottom:10px">@csrf
          <button class="row g4" style="height:30px;padding:0 12px;border-radius:15px;background:rgba(5,11,22,.75);backdrop-filter:blur(6px);font-size:12px;font-weight:600"><span class="ms" style="font-size:16px">image</span>Replace cover</button>
        </form>
      </div>
      <div class="grow" style="min-width:260px">
        @include('admin.partials.status', ['s' => $d->status])
        <h1 style="margin:12px 0 8px;font-size:48px;font-weight:800;letter-spacing:-.03em;line-height:1">{{ $d->title }}</h1>
        <div class="t2" style="font-size:14.5px">{{ $d->creator->name }} · {{ $d->genre }} · {{ $d->duration }} · Uploaded {{ $d->submitted_at?->format('M j') }}</div>
      </div>
      <div class="row">
        <button type="button" class="btn btn-md btn-primary" style="box-shadow:none" @click="$store.player.play(@js($d->toPlayer()))"><span class="ms fill" style="font-size:22px">play_arrow</span>Play</button>
        @if (in_array($d->status, ['Live', 'Unpublished', 'Flagged']))
          <form method="POST" action="{{ route('library.publish', $d) }}">@csrf
            <button class="btn btn-md" style="background:rgba(255,255,255,.08);border-color:rgba(255,255,255,.16)"><span class="ms">{{ $live ? 'visibility_off' : 'publish' }}</span>{{ $live ? 'Unpublish' : 'Republish' }}</button>
          </form>
        @endif
        <button type="button" class="icon-btn" style="width:44px;height:44px;background:rgba(240,82,82,.14);border-color:rgba(240,82,82,.35);color:var(--red)" @click="del=true"><span class="ms">delete</span></button>
      </div>
    </div>
  </div>

  @if ($d->status === 'Rejected' && $d->reject_reasons)
    <div class="panel" style="border-color:rgba(240,82,82,.3);background:rgba(240,82,82,.08)">
      <div class="sb red fs14 row g6"><span class="ms" style="font-size:18px">block</span>Rejected {{ $d->reviewed_at?->format('M j, H:i') }} · {{ implode(', ', $d->reject_reasons) }}</div>
      @if ($d->reject_note)<div class="t2 fs13 mt6">“{{ $d->reject_note }}”</div>@endif
    </div>
  @endif

  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px">
    @foreach ($stats as [$icon, $label, $value, $delta])
      <div class="card" style="border-radius:20px;padding:18px">
        <div class="row g6 fs13 muted"><span class="ms blue" style="font-size:18px">{{ $icon }}</span>{{ $label }}</div>
        <div class="num" style="font-size:28px;font-weight:800;margin-top:6px">{{ $value }}</div>
        <div class="green sb" style="font-size:12.5px;margin-top:2px">{{ $delta }}</div>
      </div>
    @endforeach
  </div>

  <div class="split-l">
    <div class="col g16" style="min-width:0">
      <div class="card">
        <div class="card-title">Plays · last 30 days</div>
        <svg viewBox="0 0 600 160" preserveAspectRatio="none" style="width:100%;height:170px;margin-top:14px;display:block">
          <defs><linearGradient id="lbg" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#0A9BFF" stop-opacity=".35"/><stop offset="1" stop-color="#0A9BFF" stop-opacity="0"/></linearGradient></defs>
          <path d="{{ $area }}" fill="url(#lbg)"/><path d="{{ $line }}" fill="none" stroke="#0A9BFF" stroke-width="2.5" vector-effect="non-scaling-stroke"/>
        </svg>
      </div>
      <form method="POST" action="{{ route('library.update', $d) }}" class="card col g14">
        @csrf @method('PUT')
        <input type="hidden" name="explicit" :value="explicit ? 1 : 0">
        <div class="row between"><div class="card-title">Edit metadata</div><button class="btn btn-sm btn-primary" style="box-shadow:none">Save changes</button></div>
        @if ($errors->any())<div class="err">{{ $errors->first() }}</div>@endif
        <div class="fields" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr))">
          <label class="field"><span>Title</span><input class="input" name="title" value="{{ old('title', $d->title) }}" required></label>
          <label class="field"><span>Genre</span><select class="input" name="genre">@foreach ($genreOpts as $g)<option @selected(old('genre', $d->genre) === $g)>{{ $g }}</option>@endforeach</select></label>
          @if ($d->is_music)
            <label class="field"><span>Mood</span><select class="input" name="mood"><option value="">—</option>@foreach ($moodOpts as $g)<option @selected(old('mood', $d->mood) === $g)>{{ $g }}</option>@endforeach</select></label>
          @endif
          <label class="field"><span>Creator</span><input class="input" value="{{ $d->creator->name }}" disabled></label>
        </div>
        <label class="field"><span>Description</span><textarea class="input" name="description" rows="3">{{ old('description', $d->description) }}</textarea></label>
        <div class="panel row between">
          <div><div class="sb fs14">Explicit content</div><div class="muted" style="font-size:12.5px;margin-top:2px">Shows an “E” badge and hides from listeners with filters on</div></div>
          <button type="button" class="switch" :class="explicit && 'on'" @click="explicit=!explicit"></button>
        </div>
      </form>
    </div>
    <div class="card tight">
      <div class="row between" style="margin-bottom:6px"><div class="card-title">Comments</div><span class="fs13 muted num">{{ Iw::k($d->comments_count) }} comments</span></div>
      @forelse ($d->comments as $cm)
        <div class="row g12 start" style="padding:12px 0;border-top:1px solid var(--line-soft)">
          <div class="avatar" style="width:32px;height:32px;background:{{ $cm->listener->art }};font-size:11px">{{ $cm->listener->initials }}</div>
          <div class="grow"><div class="fs13"><b>{{ $cm->listener->name }}</b> <span class="faint">· {{ $cm->created_at->diffForHumans() }}</span></div><div class="t2" style="font-size:13.5px;margin-top:3px;line-height:1.45">{{ $cm->body }}</div></div>
        </div>
      @empty
        <div class="fs13 muted" style="padding:12px 0;border-top:1px solid var(--line-soft)">No comments yet.</div>
      @endforelse
      <a href="{{ route('comments.index', ['content' => $d->id]) }}" class="link mt8" style="display:block">Moderate all comments</a>
    </div>
  </div>

  <div class="backdrop" x-show="del" x-cloak @click.self="del=false">
    <form method="POST" action="{{ route('library.destroy', $d) }}" class="modal sm">
      @csrf @method('DELETE')
      <div class="danger-icon"><span class="ms">delete_forever</span></div>
      <div class="modal-title">Delete “{{ $d->title }}”?</div>
      <div class="fs14 muted" style="line-height:1.5">This permanently removes the file, its stats and {{ Iw::k($d->comments_count) }} comments. This can’t be undone.</div>
      <div class="row mt6" style="width:100%">
        <button type="button" class="btn btn-md grow" @click="del=false">Cancel</button>
        <button class="btn btn-md btn-solid-danger grow">Delete</button>
      </div>
    </form>
  </div>
</div>
@endsection
