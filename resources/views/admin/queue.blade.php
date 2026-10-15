@extends('layouts.admin')
@section('title', 'Review Queue')

@php
    $ids = $items->pluck('id');
    $params = compact('tab', 'sort', 'genre', 'creator', 'view');
    $q = fn (array $over) => route('queue.index', array_merge($params, $over));
@endphp

@section('content')
<div class="page" x-data="queuePage(@js($ids), @js($items->map(fn ($c) => ['approve' => route('review.approve', $c), 'open' => route('review.show', $c)])->values()))" @keydown.window="key($event)">
  <div class="page-head">
    <div>
      <h1 class="h1">Review Queue</h1>
      <div class="sub">{{ $total }} pending submissions · Every upload is reviewed before it goes live.</div>
    </div>
    <div class="row g8 muted" style="font-size:12.5px">
      <span class="row g4"><span class="kbd lt">A</span>approve</span>
      <span class="row g4"><span class="kbd lt">R</span>reject</span>
      <span class="row g4"><span class="kbd lt">J</span><span class="kbd lt">K</span>move</span>
    </div>
  </div>

  <form method="GET" action="{{ route('queue.index') }}" class="row wrap g12" x-ref="filters">
    <input type="hidden" name="tab" value="{{ $tab }}"><input type="hidden" name="view" value="{{ $view }}">
    <div class="seg">
      @foreach ($tabs as $t)
        <a href="{{ $q(['tab' => $t['label']]) }}" class="{{ $tab === $t['label'] ? 'on' : '' }}"><span class="ms">{{ $t['icon'] }}</span>{{ $t['label'] }}<span class="count">{{ $t['n'] }}</span></a>
      @endforeach
    </div>
    <div class="grow"></div>
    <label class="filter sm"><span class="ms">swap_vert</span>
      <select name="sort" onchange="this.form.submit()"><option value="oldest" @selected($sort === 'oldest')>Oldest first</option><option value="newest" @selected($sort === 'newest')>Newest first</option></select></label>
    <label class="filter sm {{ $genre !== 'All genres' ? 'on' : '' }}"><span class="ms">category</span>
      <select name="genre" onchange="this.form.submit()"><option>All genres</option>@foreach ($genreOpts as $g)<option @selected($genre === $g)>{{ $g }}</option>@endforeach</select></label>
    <label class="filter sm {{ $creator !== 'All creators' ? 'on' : '' }}"><span class="ms">person</span>
      <select name="creator" onchange="this.form.submit()"><option>All creators</option>@foreach ($creatorOpts as $g)<option @selected($creator === $g)>{{ $g }}</option>@endforeach</select></label>
    <div class="viewtoggle">
      <a href="{{ $q(['view' => 'grid']) }}" title="Grid" class="{{ $view === 'grid' ? 'on' : '' }}"><span class="ms">grid_view</span></a>
      <a href="{{ $q(['view' => 'table']) }}" title="Table" class="{{ $view === 'table' ? 'on' : '' }}"><span class="ms">view_list</span></a>
    </div>
  </form>

  <form method="POST" action="{{ route('queue.bulk') }}" x-ref="bulk" class="bulk" x-show="sel.length" x-cloak>
    @csrf
    <template x-for="id in sel" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
    <span class="ms fill blue">check_box</span>
    <span class="sb fs14"><span x-text="sel.length"></span> selected</span>
    <button type="button" class="link" style="color:var(--blue-text)" @click="sel = [...ids]">Select all {{ $items->count() }}</button>
    <div class="grow"></div>
    <button type="button" class="btn btn-sm btn-text" @click="sel = []">Clear</button>
    <button name="action" value="reject" class="btn btn-sm btn-danger"><span class="ms" style="font-size:18px">close</span>Reject</button>
    <button name="action" value="approve" class="btn btn-sm btn-primary" style="box-shadow:none"><span class="ms" style="font-size:18px">check</span>Approve & publish</button>
  </form>

  @if ($items->isEmpty())
    <div class="card col" style="border-radius:28px;padding:80px 24px;align-items:center;text-align:center;gap:14px;background:rgba(15,35,64,.45)">
      <div style="width:168px;height:168px;border-radius:50%;border:1px solid rgba(10,155,255,.15);display:flex;align-items:center;justify-content:center;margin-bottom:10px">
        <div style="width:124px;height:124px;border-radius:50%;border:1px solid rgba(10,155,255,.3);display:flex;align-items:center;justify-content:center">
          <div style="width:84px;height:84px;border-radius:50%;background:radial-gradient(circle at 35% 30%, #46B8FF, #0A6FCC);display:flex;align-items:center;justify-content:center;box-shadow:0 0 50px rgba(10,155,255,.5)"><span class="ms fill" style="font-size:42px">headphones</span></div>
        </div>
      </div>
      <div style="font-size:26px;font-weight:800;letter-spacing:-.02em">All caught up 🎧</div>
      <div class="muted fs15" style="max-width:380px;line-height:1.5">Nothing waiting for review. New uploads from creators will land here automatically.</div>
      <div class="row mt8">
        @if ($genre !== 'All genres' || $creator !== 'All creators')<a href="{{ route('queue.index', ['tab' => $tab]) }}" class="btn">Clear filters</a>@endif
        <a href="{{ route('library.index') }}" class="btn btn-primary">Browse library</a>
      </div>
    </div>
  @elseif ($view === 'grid')
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(232px,1fr));gap:18px">
      @foreach ($items as $i => $it)
        <div class="card col" style="border-radius:22px;padding:10px;gap:12px;transition:transform .2s, border-color .2s"
             :style="(sel.includes({{ $it->id }}) || focus === {{ $i }}) ? { borderColor: 'rgba(10,155,255,.6)' } : {}">
          <a href="{{ route('review.show', $it) }}" style="aspect-ratio:{{ $it->is_music ? '1' : '16/10' }};border-radius:16px;background:{{ $it->art }};position:relative;overflow:hidden;display:block;color:#fff">
            <button type="button" class="cb lg" :class="sel.includes({{ $it->id }}) && 'on'" style="position:absolute;top:10px;left:10px" @click.prevent.stop="toggle({{ $it->id }})"><span class="ms">check</span></button>
            <span class="overlay-pill" style="position:absolute;top:10px;right:10px;color:{{ $it->urgent ? '#F5C46B' : '#C9D6E8' }}"><span class="ms" style="font-size:14px">schedule</span>{{ \App\Support\Iw::waiting($it->wait_hours) }}</span>
            <button type="button" class="playdot" style="position:absolute;right:10px;bottom:10px" @click.prevent.stop="$store.player.play(@js($it->toPlayer()))"><span class="ms">play_arrow</span></button>
            <span class="num" style="position:absolute;left:10px;bottom:10px;font-size:11.5px;font-weight:600;padding:4px 8px;border-radius:9px;background:rgba(5,11,22,.6)">{{ $it->duration }}</span>
          </a>
          <a href="{{ route('review.show', $it) }}" style="padding:0 4px;min-width:0;color:#fff">
            <div class="b ell fs15">{{ $it->title }}</div>
            <div class="fs13 muted" style="margin-top:2px">{{ $it->creator->name }}</div>
            <div class="row g6 fs12 t3 mt8">
              <span class="chip blue"><span class="ms">{{ $it->icon }}</span>{{ $it->type }}</span>
              <span class="chip">{{ $it->genre }}</span>
              <span class="faint" style="margin-left:auto">{{ $it->submitted_at->format('M j, H:i') }}</span>
            </div>
          </a>
          <div class="row g8">
            <a href="{{ route('review.show', ['content' => $it, 'reject' => 1]) }}" class="btn btn-sm grow" style="height:36px">
              <span class="ms" style="font-size:17px">close</span>Reject</a>
            <form method="POST" action="{{ route('review.approve', $it) }}" class="grow" style="display:flex">@csrf<input type="hidden" name="return" value="queue">
              <button class="btn btn-sm btn-soft grow" style="height:36px"><span class="ms" style="font-size:17px">check</span>Approve</button></form>
          </div>
        </div>
      @endforeach
    </div>
  @else
    <div class="card flush r22">
      <div class="table-wrap" style="max-height:640px">
        <table class="tbl" style="min-width:960px">
          <thead><tr>
            <th style="width:48px"><button type="button" class="cb" :class="sel.length === ids.length && 'on'" @click="sel = sel.length === ids.length ? [] : [...ids]"><span class="ms">check</span></button></th>
            <th>Submission</th><th>Type</th><th>Genre</th><th class="right">Duration</th><th>Submitted</th><th>Waiting</th><th class="right">Actions</th>
          </tr></thead>
          <tbody>
            @foreach ($items as $i => $it)
              <tr :class="(sel.includes({{ $it->id }}) || focus === {{ $i }}) && 'sel'">
                <td><button type="button" class="cb" :class="sel.includes({{ $it->id }}) && 'on'" @click="toggle({{ $it->id }})"><span class="ms">check</span></button></td>
                <td><a href="{{ route('review.show', $it) }}" class="row g12" style="color:#fff"><div class="thumb" style="width:{{ $it->is_music ? '44px' : '70px' }};background:{{ $it->art }}"></div><div><div class="sb">{{ $it->title }}</div><div class="muted" style="font-size:12.5px">{{ $it->creator->name }}</div></div></a></td>
                <td><span class="row g4 sb" style="color:var(--blue-text);font-size:12.5px"><span class="ms" style="font-size:16px">{{ $it->icon }}</span>{{ $it->type }}</span></td>
                <td class="t2">{{ $it->genre }}</td>
                <td class="right num t2">{{ $it->duration }}</td>
                <td class="t3 nowrap">{{ $it->submitted_at->format('M j, H:i') }}</td>
                <td><span class="pill {{ $it->urgent ? 'amber' : 'neutral' }}" style="font-weight:700">{{ \App\Support\Iw::waiting($it->wait_hours) }}</span></td>
                <td><div class="row g6 end">
                  <button type="button" title="Preview" class="icon-btn sm" @click="$store.player.play(@js($it->toPlayer()))"><span class="ms fill" style="font-size:19px">play_arrow</span></button>
                  <a href="{{ route('review.show', ['content' => $it, 'reject' => 1]) }}" title="Reject" class="icon-btn sm r"><span class="ms" style="font-size:19px">close</span></a>
                  <form method="POST" action="{{ route('review.approve', $it) }}">@csrf<input type="hidden" name="return" value="queue"><button title="Approve" class="icon-btn sm a"><span class="ms" style="font-size:19px">check</span></button></form>
                </div></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <div class="tbl-foot"><span>Showing 1–{{ $items->count() }} of {{ $items->count() }}</span><div class="pager"><span><span class="ms">chevron_left</span></span><span class="on">1</span><span><span class="ms">chevron_right</span></span></div></div>
    </div>
  @endif
</div>
@endsection

@push('scripts')
<script>
function queuePage(ids, links) {
  return {
    ids, links, sel: [], focus: -1,
    toggle(id) { this.sel = this.sel.includes(id) ? this.sel.filter(x => x !== id) : [...this.sel, id]; },
    key(e) {
      if (/INPUT|TEXTAREA|SELECT/.test(e.target.tagName) || e.metaKey || e.ctrlKey || !this.ids.length) return;
      const k = e.key.toLowerCase();
      if (k === 'j') this.focus = Math.min(this.ids.length - 1, this.focus + 1);
      else if (k === 'k') this.focus = Math.max(0, this.focus - 1);
      else if (k === 'a' && this.focus >= 0) iwPost(this.links[this.focus].approve, { return: 'queue' });
      else if (k === 'r' && this.focus >= 0) window.location = this.links[this.focus].open + '?reject=1';
      else if (k === 'enter' && this.focus >= 0) window.location = this.links[this.focus].open;
    },
  };
}
</script>
@endpush
