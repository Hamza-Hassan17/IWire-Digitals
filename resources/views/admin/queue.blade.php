@extends('layouts.admin')
@section('title', 'Review Queue')

@section('content')
<div class="page" x-data="{ ids: @js($items->pluck('id')), sel: [], toggle(id) { this.sel = this.sel.includes(id) ? this.sel.filter(x => x !== id) : [...this.sel, id] } }">
  <div class="page-head">
    <div>
      <h1 class="h1">Review Queue</h1>
      <div class="sub">{{ $total }} waiting · Uploads go live only after you approve them.</div>
    </div>
  </div>

  <div class="seg">
    @foreach ($tabs as $t)
      <a href="{{ route('queue.index', ['tab' => $t['label']]) }}" class="{{ $tab === $t['label'] ? 'on' : '' }}"><span class="ms">{{ $t['icon'] }}</span>{{ $t['label'] }}<span class="count">{{ $t['n'] }}</span></a>
    @endforeach
  </div>

  <form method="POST" action="{{ route('queue.bulk') }}" class="bulk" x-show="sel.length" x-cloak>
    @csrf
    <template x-for="id in sel" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
    <span class="sb fs14"><span x-text="sel.length"></span> selected</span>
    <div class="grow"></div>
    <button type="button" class="btn btn-sm btn-text" @click="sel = []">Clear</button>
    <button name="action" value="reject" class="btn btn-sm btn-danger">Reject</button>
    <button name="action" value="approve" class="btn btn-sm btn-primary" style="box-shadow:none">Approve & publish</button>
  </form>

  @if ($items->isEmpty())
    <div class="empty">
      <span class="ms green" style="font-size:44px">task_alt</span>
      <div class="b mt8" style="font-size:20px">All caught up</div>
      <div class="fs14 muted mt4">Nothing waiting for review. New uploads from creators will appear here.</div>
    </div>
  @else
    <div class="card flush r22 table-wrap">
      <table class="tbl" style="min-width:760px">
        <thead><tr>
          <th style="width:48px"><button type="button" class="cb" :class="sel.length === ids.length && 'on'" @click="sel = sel.length === ids.length ? [] : [...ids]"><span class="ms">check</span></button></th>
          <th>Submission</th><th>Type</th><th>Waiting</th><th class="right">Decision</th>
        </tr></thead>
        <tbody>
          @foreach ($items as $it)
            <tr :class="sel.includes({{ $it->id }}) && 'sel'">
              <td><button type="button" class="cb" :class="sel.includes({{ $it->id }}) && 'on'" @click="toggle({{ $it->id }})"><span class="ms">check</span></button></td>
              <td><a href="{{ route('review.show', $it) }}" class="row g12" style="color:#fff"><div class="thumb" style="width:{{ $it->is_music ? '44px' : '70px' }};background:{{ $it->art }}"></div><div><div class="sb">{{ $it->title }}</div><div class="muted" style="font-size:12.5px">{{ $it->creator->name }} · {{ $it->genre }} · {{ $it->duration }}</div></div></a></td>
              <td><span class="row g4 sb" style="color:var(--blue-text);font-size:12.5px"><span class="ms" style="font-size:16px">{{ $it->icon }}</span>{{ $it->is_music ? 'Track' : 'Film' }}</span></td>
              <td><span class="pill {{ $it->urgent ? 'amber' : 'neutral' }}">{{ \App\Support\Iw::waitShort($it->wait_hours) }}</span></td>
              <td><div class="row g6 end">
                <a href="{{ route('review.show', $it) }}" class="btn btn-sm">Review</a>
                <form method="POST" action="{{ route('review.approve', $it) }}">@csrf<input type="hidden" name="return" value="queue"><button class="btn btn-sm btn-soft">Approve</button></form>
              </div></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>
@endsection
