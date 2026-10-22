@extends('layouts.admin')
@section('title', 'Listeners')

@php use App\Support\Iw; @endphp

@section('content')
<div class="page" x-data="{ confirm: false }">
  <div class="page-head">
    <div><h1 class="h1">Listeners</h1><div class="sub">{{ number_format($total) }} accounts · {{ number_format($newThisWeek) }} joined this week</div></div>
    <a href="{{ route('listeners.export') }}" class="btn"><span class="ms">download</span>Export CSV</a>
  </div>
  <form method="GET" class="row wrap">
    <input type="hidden" name="status" value="{{ $status }}">
    <label class="searchbox"><span class="ms">search</span><input name="q" value="{{ $q }}" placeholder="Search name or email" x-data @input.debounce.500ms="$el.form.submit()" autocomplete="off"></label>
    <div class="seg sm">
      @foreach (['All', 'Active', 'Suspended'] as $s)
        <a href="{{ route('listeners.index', array_filter(['q' => $q, 'status' => $s === 'All' ? null : $s])) }}" class="{{ $status === $s ? 'on' : '' }}">{{ $s }}</a>
      @endforeach
    </div>
  </form>

  <div class="card flush r22">
    <div class="table-wrap" style="max-height:620px">
      <table class="tbl" style="min-width:860px">
        <thead><tr><th>Listener</th><th>Email</th><th>Joined</th><th>Last active</th><th class="right">Streams</th><th>Status</th></tr></thead>
        <tbody>
          @forelse ($rows as $u)
            <tr class="click {{ $open?->id === $u->id ? 'sel' : '' }}" onclick="window.location='{{ request()->fullUrlWithQuery(['open' => $u->id]) }}'">
              <td><div class="row g12"><div class="avatar" style="width:36px;height:36px;background:{{ $u->art }};font-size:12px">{{ $u->initials }}</div><span class="sb">{{ $u->name }}</span></div></td>
              <td class="t3">{{ $u->email }}</td>
              <td class="t2 nowrap">{{ $u->joined_at?->format('M j, Y') }}</td>
              <td class="t2">{{ $u->last_active_at?->diffForHumans() }}</td>
              <td class="right num sb">{{ Iw::k($u->streams) }}</td>
              <td>@include('admin.partials.status', ['s' => $u->status])</td>
            </tr>
          @empty
            <tr><td colspan="6" class="center muted" style="padding:40px">No listeners match.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @include('admin.partials.pager', ['p' => $rows])
  </div>

  @if ($open)
    @php $sus = $open->status === 'Suspended'; $close = request()->fullUrlWithoutQuery(['open']); @endphp
    <a href="{{ $close }}" class="drawer-bg"></a>
    <aside class="drawer">
      <div class="drawer-head">
        <a href="{{ $close }}" class="icon-btn sm" style="position:absolute;top:16px;right:16px;width:36px;height:36px;color:#fff"><span class="ms">close</span></a>
        <div class="avatar" style="width:72px;height:72px;background:{{ $open->art }};font-size:24px;font-weight:800;border:3px solid rgba(255,255,255,.15)">{{ $open->initials }}</div>
        <div style="font-size:22px;font-weight:800;margin-top:14px">{{ $open->name }}</div>
        <div class="muted" style="font-size:13.5px;margin-top:3px">{{ $open->email }}</div>
        @php $u = $open->user; @endphp
        <div class="row g8 mt12 wrap">
          @include('admin.partials.status', ['s' => $open->status])
          @if ($u)<span class="pill blue">App account</span>@endif
          @foreach (array_filter([$u?->gender ? ucfirst(str_replace('_', ' ', $u->gender)) : null, $u?->date_of_birth ? 'Born '.$u->date_of_birth->format('M j, Y') : null, $u?->country ?? $open->location]) as $bit)
            <span class="pill neutral">{{ $bit }}</span>
          @endforeach
        </div>
        @if ($u?->bio)<div class="t2 fs13 mt12" style="line-height:1.5">{{ $u->bio }}</div>@endif
      </div>
      <div class="drawer-body">
        <div class="stat3">
          <div class="inset"><div class="v" style="font-size:18px">{{ Iw::k($open->streams) }}</div><div class="l">Streams</div></div>
          <div class="inset"><div class="v" style="font-size:18px">{{ $open->joined_at?->format('M j') }}</div><div class="l">Joined</div></div>
          <div class="inset"><div class="v" style="font-size:18px">{{ $open->last_active_at?->diffForHumans(null, true, true) }}</div><div class="l">Last active</div></div>
        </div>
        <div>
          <div class="label-xs" style="font-size:12px;margin-bottom:8px">Recent listening & watching</div>
          @foreach ($history as $k => $h)
            <div class="row g12" style="padding:8px 0">
              <div class="thumb" style="width:{{ $h->is_music ? '40px' : '64px' }};height:40px;background:{{ $h->art }}"></div>
              <div class="grow"><div class="sb ell" style="font-size:13.5px">{{ $h->title }}</div><div class="fs12 muted row g4"><span class="ms" style="font-size:14px">{{ $h->icon }}</span>{{ $h->creator->name }}</div></div>
              <span class="fs12 faint nowrap">{{ ['12 min ago', '2 hrs ago', 'Yesterday', 'Yesterday', now()->subDays(4)->format('M j')][$k] }}</span>
            </div>
          @endforeach
        </div>
        <div>
          <div class="label-xs" style="font-size:12px;margin-bottom:8px">Comments made</div>
          @forelse ($open->comments as $c)
            <div class="inset" style="padding:12px 14px;margin-bottom:8px"><div style="font-size:13.5px;line-height:1.45;color:#E6EEF8">“{{ $c->body }}”</div><div class="fs12 muted mt6">on {{ $c->content->title }} · {{ $c->created_at->diffForHumans() }}</div></div>
          @empty
            <div class="fs13 muted">No comments.</div>
          @endforelse
        </div>
      </div>
      <div class="drawer-foot">
        <form method="POST" action="{{ route('listeners.suspend', $open) }}" class="grow" style="display:flex">@csrf
          <button class="btn btn-md grow" style="font-weight:700;{{ $sus ? 'background:rgba(52,199,123,.14);color:#5FD99A;border-color:rgba(52,199,123,.35)' : 'background:rgba(245,176,65,.12);color:#F5C46B;border-color:rgba(245,176,65,.35)' }}"><span class="ms">{{ $sus ? 'restart_alt' : 'block' }}</span>{{ $sus ? 'Reactivate' : 'Suspend' }}</button>
        </form>
        <button type="button" class="btn btn-md red" style="background:rgba(240,82,82,.1);border-color:transparent" @click="confirm=true"><span class="ms">delete</span>Delete</button>
      </div>
    </aside>
    <div class="backdrop" style="z-index:120" x-show="confirm" x-cloak @click.self="confirm=false">
      <form method="POST" action="{{ route('listeners.destroy', $open) }}" class="modal sm">@csrf @method('DELETE')
        <div class="danger-icon"><span class="ms">person_remove</span></div>
        <div class="modal-title">Delete {{ $open->name }}?</div>
        <div class="fs14 muted" style="line-height:1.5">Their profile, history and comments will be permanently removed.</div>
        <div class="row mt6" style="width:100%"><button type="button" class="btn btn-md grow" @click="confirm=false">Cancel</button><button class="btn btn-md btn-solid-danger grow">Delete account</button></div>
      </form>
    </div>
  @endif
</div>
@endsection
