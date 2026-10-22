@extends('layouts.admin')
@section('title', 'Review · '.$c->title)

@php use App\Support\Iw; $isMusic = $c->is_music; @endphp

@section('content')
<div class="page" x-data="reviewPage({{ request()->boolean('reject') ? 'true' : 'false' }}, @js(old('reasons', [])))" @keydown.escape.window="modal=null">
  <div class="row wrap g12">
    <a href="{{ route('queue.index') }}" class="back"><span class="ms" style="font-size:19px">arrow_back</span>Review Queue</a>
    <span class="muted" style="font-size:13.5px">Submission <b class="num" style="color:#fff">{{ $pos }}</b> of {{ $total }}</span>
    <div class="grow"></div>
    <div class="row g6">
      <a href="{{ route('review.show', $prev) }}" title="Previous" class="circle-btn" x-ref="prev"><span class="ms">keyboard_arrow_up</span></a>
      <a href="{{ route('review.show', $next) }}" title="Next" class="circle-btn" x-ref="next"><span class="ms">keyboard_arrow_down</span></a>
    </div>
  </div>

  @if ($errors->any())
    <div class="panel red fs13">{{ $errors->first() }}</div>
  @endif

  <div class="hero" style="min-height:300px">
    <div class="hero-bg" style="background:{{ $c->art }}"></div>
    <div class="hero-shade"></div>
    <div class="hero-body">
      <div class="hero-cover" style="width:{{ $isMusic ? '220px' : '330px' }};max-width:100%;background:{{ $c->art }}"></div>
      <div class="col g12 grow" style="min-width:280px">
        <div class="row g8 wrap">
          <span class="pill" style="font-size:11.5px;font-weight:700;letter-spacing:.06em;padding:5px 10px;border-radius:12px;background:rgba(245,176,65,.18);color:var(--amber)"><span class="dot" style="background:#F5B041"></span>PENDING REVIEW</span>
          <span class="pill" style="padding:5px 10px;border-radius:12px;background:rgba(5,11,22,.5);color:var(--text-2)"><span class="ms" style="font-size:15px">{{ $c->icon }}</span>{{ $isMusic ? 'Track' : 'Film' }}</span>
          <span class="pill" style="padding:5px 10px;border-radius:12px;background:rgba(5,11,22,.5);color:{{ $c->urgent ? '#F5C46B' : '#C9D6E8' }}">{{ Iw::waiting($c->wait_hours) }}</span>
        </div>
        <h1 class="hero-title" x-text="title">{{ $c->title }}</h1>
        <div class="row wrap t2" style="font-size:14.5px">
          <span class="avatar" style="width:26px;height:26px;background:{{ $cr->art }};font-size:10.5px">{{ $cr->initials }}</span>
          <b style="color:#fff">{{ $cr->name }}</b><span class="faint">•</span><span>{{ $c->duration }}</span><span class="faint">•</span><span>Submitted {{ $c->submitted_at->format('M j, H:i') }}</span>
        </div>
        <div class="row g8 wrap">
          <span class="tag" x-text="genre"></span>
          @if ($isMusic)<span class="tag" x-text="mood || 'No mood'"></span>@else<span class="tag">Feature</span>@endif
          <span class="tag" x-text="explicit ? 'Explicit' : 'Clean'"></span>
          <span class="tag">{{ $isMusic ? 'Single' : 'English' }}</span>
        </div>
      </div>
      <div class="row wrap">
        <button type="button" class="btn btn-lg btn-danger" @click="modal='reject'"><span class="ms" style="font-size:20px">block</span>Reject</button>
        <button type="button" class="btn btn-lg" style="background:rgba(255,255,255,.08);border-color:rgba(255,255,255,.18);font-weight:700" @click="modal='feature'; feature = @js($rows->first()['key'] ?? '')"><span class="ms" style="font-size:20px">star</span>Approve & Feature</button>
        <button type="submit" form="approveForm" class="btn btn-lg btn-primary" style="box-shadow:0 10px 30px rgba(10,155,255,.4)"><span class="ms" style="font-size:20px">check_circle</span>Approve & Publish</button>
      </div>
    </div>
  </div>

  <div class="split-r">
    <div class="col g16" style="min-width:0">
      <div class="card">
        <div class="row between" style="margin-bottom:14px"><div class="card-title">{{ $isMusic ? 'Listen' : 'Watch' }}</div><span class="muted" style="font-size:12.5px">{{ $c->duration }}</span></div>
        @if ($c->file_path)
          @if ($isMusic)
            <audio controls preload="metadata" src="{{ route('media.preview', $c) }}" style="width:100%"></audio>
          @else
            <video controls preload="metadata" src="{{ route('media.preview', $c) }}" poster="{{ $c->banner_url }}" style="width:100%;border-radius:16px;background:#000;aspect-ratio:16/9"></video>
          @endif
        @else
          <div class="panel muted fs13 row g8"><span class="ms amber">warning</span>No {{ $isMusic ? 'audio' : 'video' }} file was uploaded with this submission.</div>
        @endif
      </div>
      <form id="approveForm" method="POST" action="{{ route('review.approve', $c) }}" class="card col g16">
        @csrf
        <input type="hidden" name="feature" :value="feature">
        <input type="hidden" name="explicit" :value="explicit ? 1 : 0">
        <div class="row between wrap"><div class="card-title">Metadata</div><span class="row g6 muted" style="font-size:12.5px"><span class="ms" style="font-size:16px">edit</span>Edit any field — changes save with the decision</span></div>
        <div class="fields">
          <label class="field"><span>Title</span><input class="input" name="title" x-model="title" required maxlength="160"></label>
          <label class="field"><span>Genre</span>
            <select class="input" name="genre" x-model="genre">@foreach ($genreOpts->push($c->genre)->unique() as $g)<option>{{ $g }}</option>@endforeach</select></label>
          @if ($isMusic)
            <label class="field"><span>Mood</span>
              <select class="input" name="mood" x-model="mood"><option value="">—</option>@foreach ($moodOpts as $g)<option>{{ $g }}</option>@endforeach</select></label>
          @endif
        </div>
        <label class="field"><span>Description</span><textarea class="input" name="description" rows="3">{{ $c->description }}</textarea></label>
        <div class="panel row between">
          <div><div class="sb fs14">Explicit content</div><div class="muted" style="font-size:12.5px;margin-top:2px">Shows an “E” badge and hides from listeners with filters on</div></div>
          <button type="button" class="switch" :class="explicit && 'on'" @click="explicit=!explicit"></button>
        </div>
      </form>
    </div>

    <div class="col g16">
      <div class="card tight">
        @php $passed = collect($checks)->where(2, true)->count(); @endphp
        <div class="row between" style="margin-bottom:12px"><div class="card-title">Automated checks</div><span class="fs12 sb muted">{{ $passed }}/{{ count($checks) }} passed{{ $dup ? ' · 1 warning' : '' }}</span></div>
        @foreach ($checks as [$label, $value, $ok])
          <div class="row g12" style="padding:11px 0;border-top:1px solid var(--line-soft)">
            <span class="ms fill" style="font-size:21px;color:{{ $ok ? '#5FD99A' : '#F5C46B' }}">{{ $ok ? 'check_circle' : 'error' }}</span>
            <div class="grow"><div class="sb" style="font-size:13.5px">{{ $label }}</div><div class="muted" style="font-size:12.5px">{{ $value }}</div></div>
          </div>
        @endforeach
        @if ($dup)
          <div class="row g12 start mt8" style="padding:14px;border-radius:16px;background:rgba(249,128,52,.1);border:1px solid rgba(249,128,52,.3)">
            <div class="art" style="width:44px;height:44px;border-radius:10px;background:{{ $dup['item']->art }}"></div>
            <div class="grow">
              <div class="row g6 b orange" style="font-size:13.5px"><span class="ms" style="font-size:17px">content_copy</span>Possible duplicate · {{ $dup['score'] }}% title match</div>
              <div class="t2" style="font-size:12.5px;margin-top:3px">Matches “{{ $dup['item']->title }}” already in the library by {{ $cr->name }}.</div>
              <a href="{{ route('library.show', $dup['item']) }}" class="link" style="font-size:12.5px;display:inline-block;margin-top:6px">Compare side by side</a>
            </div>
          </div>
        @endif
      </div>

      <div class="card tight col g14">
        <div class="card-title">Creator</div>
        <div class="row g14">
          <div class="avatar" style="width:56px;height:56px;background:{{ $cr->art }};font-weight:800;font-size:18px;border:2px solid rgba(255,255,255,.15)">{{ $cr->initials }}</div>
          <div class="grow"><div class="b" style="font-size:16px">{{ $cr->name }}</div><div class="fs13 muted ell">{{ $cr->email }}</div><div class="faint" style="font-size:12.5px;margin-top:2px">{{ $cr->type }} · Joined {{ $cr->joined_at?->format('M Y') }}</div></div>
        </div>
        <div class="stat3">
          <div class="inset"><div class="v">{{ $stats['uploads'] }}</div><div class="l">Uploads</div></div>
          <div class="inset"><div class="v green">{{ $stats['approved'] }}</div><div class="l">Approved</div></div>
          <div class="inset"><div class="v red">{{ $stats['rejected'] }}</div><div class="l">Rejected</div></div>
        </div>
        <div><div class="row between muted" style="font-size:12.5px;margin-bottom:6px"><span>Approval rate</span><b style="color:#fff">{{ $stats['rate'] }}%</b></div><div class="bar"><div style="width:{{ $stats['rate'] }}%"></div></div></div>
        <a href="{{ route('creators.show', $cr) }}" class="btn" style="height:40px;font-size:13.5px">View creator profile<span class="ms" style="font-size:18px">arrow_forward</span></a>
      </div>
    </div>
  </div>

  {{-- Reject modal --}}
  <div class="backdrop" x-show="modal==='reject'" x-cloak @click.self="modal=null">
    <form method="POST" action="{{ route('review.reject', $c) }}" class="modal" @submit="if (!reasons.length) $event.preventDefault()">
      @csrf
      <div class="row g16">
        <div class="art" style="width:52px;height:52px;border-radius:14px;background:{{ $c->art }}"></div>
        <div class="grow"><div class="modal-title">Reject “<span x-text="title"></span>”?</div><div class="muted" style="font-size:13.5px;margin-top:3px">{{ $cr->name }} will be notified with your reason and note.</div></div>
        <button type="button" class="icon-btn xs" @click="modal=null"><span class="ms">close</span></button>
      </div>
      <div>
        <div class="sb muted" style="font-size:12.5px;margin-bottom:10px">Reason</div>
        <div class="row wrap g8">
          @foreach ($reasons as $r)
            <button type="button" class="reason" :class="reasons.includes(@js($r)) && 'on'" @click="toggleReason(@js($r))"><span class="ms" x-show="reasons.includes(@js($r))">check</span>{{ $r }}</button>
          @endforeach
        </div>
        <template x-for="r in reasons" :key="r"><input type="hidden" name="reasons[]" :value="r"></template>
      </div>
      <label class="field"><span style="font-size:12.5px">Note to creator</span><textarea class="input" name="note" rows="4" style="resize:none" placeholder="Explain what needs to change so they can resubmit…">{{ old('note') }}</textarea></label>
      <div class="row end">
        <button type="button" class="btn btn-md btn-text" @click="modal=null">Cancel</button>
        <button class="btn btn-md btn-solid-danger" :aria-disabled="!reasons.length"><span class="ms">send</span>Send rejection</button>
      </div>
    </form>
  </div>

  {{-- Feature modal --}}
  <div class="backdrop" x-show="modal==='feature'" x-cloak @click.self="modal=null; feature=''">
    <div class="modal" style="width:460px;padding:26px;gap:16px">
      <div><div class="modal-title">Approve & feature</div><div class="muted" style="font-size:13.5px;margin-top:4px">Publish now and add to a curated row on the app home.</div></div>
      @foreach ($rows as $r)
        <button type="button" class="option" :class="feature === @js($r['key']) && 'on'" @click="feature = @js($r['key'])">
          <span class="ms blue" style="font-size:22px">{{ $r['icon'] }}</span>
          <div class="grow"><div class="b" style="font-size:14.5px">{{ $r['label'] }}</div><div class="muted" style="font-size:12.5px">{{ $r['sub'] }}</div></div>
          <span class="radio"></span>
        </button>
      @endforeach
      <div class="row end mt4">
        <button type="button" class="btn btn-md btn-text" @click="modal=null; feature=''">Cancel</button>
        <button type="button" class="btn btn-md btn-primary" style="box-shadow:none" @click="$nextTick(() => document.getElementById('approveForm').submit())"><span class="ms">star</span>Publish & feature</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
function reviewPage(openReject, oldReasons) {
  return {
    modal: openReject ? 'reject' : null, reasons: oldReasons, feature: '',
    title: @js(old('title', $c->title)), genre: @js(old('genre', $c->genre)), mood: @js(old('mood', $c->mood ?? '')), explicit: {{ $c->explicit ? 'true' : 'false' }},
    toggleReason(r) { this.reasons = this.reasons.includes(r) ? this.reasons.filter(x => x !== r) : [...this.reasons, r]; },
  };
}
</script>
@endpush
