@extends('layouts.admin')
@section('title', 'Review · '.$c->title)

@php use App\Support\Iw; $isMusic = $c->is_music; @endphp

@section('content')
<div class="page" x-data="reviewPage({{ $secs }}, {{ request()->boolean('reject') ? 'true' : 'false' }}, @js(old('reasons', [])))" @keydown.window="key($event)">
  <div class="row wrap g12">
    <a href="{{ route('queue.index') }}" class="back"><span class="ms" style="font-size:19px">arrow_back</span>Review Queue</a>
    <span class="muted" style="font-size:13.5px">Submission <b class="num" style="color:#fff">{{ $pos }}</b> of {{ $total }}</span>
    <div class="grow"></div>
    <div class="row muted" style="font-size:12.5px">
      <span class="row g6"><span class="kbd lt">A</span>Approve</span>
      <span class="row g6"><span class="kbd lt">R</span>Reject</span>
      <span class="row g6"><span class="kbd lt">J</span><span class="kbd lt">K</span>Next / prev</span>
    </div>
    <div class="row g6">
      <a href="{{ route('review.show', $prev) }}" title="Previous (K)" class="circle-btn" x-ref="prev"><span class="ms">keyboard_arrow_up</span></a>
      <a href="{{ route('review.show', $next) }}" title="Next (J)" class="circle-btn" x-ref="next"><span class="ms">keyboard_arrow_down</span></a>
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
      @if ($isMusic)
        <div class="card">
          <div class="row between" style="margin-bottom:18px"><div class="card-title">Audio preview</div><div class="muted" style="font-size:12.5px">WAV · 24-bit · 48 kHz · Stereo</div></div>
          <div class="row" style="gap:18px">
            <button type="button" @click="playing=!playing" class="avatar" style="width:58px;height:58px;background:var(--blue);box-shadow:0 8px 24px rgba(10,155,255,.4)"><span class="ms fill" style="font-size:34px" x-text="playing ? 'pause' : 'play_arrow'">play_arrow</span></button>
            <div class="grow">
              <div class="wave" style="height:96px" @click="seek($event)" @mousemove="hover($event)" @mouseleave="hx=null">
                @foreach ($wave as $k => $h)
                  <i style="height:{{ max(8, $h) }}%" :style="{ background: {{ $k / count($wave) }} < pos ? '#0A9BFF' : (hx !== null && {{ $k / count($wave) }} < hx ? 'rgba(10,155,255,.45)' : 'rgba(255,255,255,.18)') }"></i>
                @endforeach
                <div :style="`position:absolute;top:-4px;bottom:-4px;left:${pos*100}%;width:2px;background:#fff;border-radius:1px;box-shadow:0 0 10px rgba(10,155,255,.9)`"></div>
                <div x-show="hx !== null" x-cloak class="num" :style="`position:absolute;top:-30px;left:${hx*100}%;transform:translateX(-50%);font-size:11.5px;font-weight:700;padding:3px 8px;border-radius:8px;background:#fff;color:#050B16`" x-text="fmt(hx*secs)"></div>
              </div>
              <div class="row between fs12 muted num mt8"><span x-text="fmt(pos*secs)"></span><span class="row g16"><span>Peak −0.8 dB</span><span>LUFS −9.4</span></span><span>{{ $c->duration }}</span></div>
            </div>
          </div>
        </div>
      @else
        <div class="card" style="padding:16px">
          <div style="aspect-ratio:16/9;border-radius:18px;background:{{ $c->art }};position:relative;overflow:hidden">
            <div style="position:absolute;inset:0;background:linear-gradient(180deg, rgba(0,0,0,0) 55%, rgba(0,0,0,.75))"></div>
            <button type="button" @click="playing=!playing" class="avatar" style="position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);width:76px;height:76px;background:rgba(5,11,22,.6);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,.2)"><span class="ms fill" style="font-size:44px" x-text="playing ? 'pause' : 'play_arrow'">play_arrow</span></button>
            <div class="col g8" style="position:absolute;left:20px;right:20px;bottom:16px">
              <div style="height:18px;display:flex;align-items:center;cursor:pointer;position:relative" @click="seek($event)" @mousemove="hover($event)" @mouseleave="hx=null">
                <div style="height:5px;width:100%;border-radius:3px;background:rgba(255,255,255,.25);position:relative">
                  <div :style="`position:absolute;left:0;top:0;bottom:0;width:${pos*100}%;background:#0A9BFF;border-radius:3px`"></div>
                  <div :style="`position:absolute;top:50%;left:${pos*100}%;width:14px;height:14px;border-radius:50%;background:#fff;transform:translate(-50%,-50%)`"></div>
                </div>
                <div x-show="hx !== null" x-cloak class="col g6" :style="`position:absolute;bottom:26px;left:${hx*100}%;transform:translateX(-50%);align-items:center`">
                  @foreach (range(0, 7) as $k)
                    <div x-show="Math.min(7, Math.floor(hx*8)) === {{ $k }}" style="width:168px;aspect-ratio:16/9;border-radius:10px;background:{{ Iw::art($c->art_seed + $k, $k % 5) }};border:2px solid #fff;box-shadow:0 10px 30px rgba(0,0,0,.6)"></div>
                  @endforeach
                  <span class="num" style="font-size:11.5px;font-weight:700;padding:2px 8px;border-radius:7px;background:#fff;color:#050B16" x-text="fmt(hx*secs)"></span>
                </div>
              </div>
              <div class="row num fs13" style="gap:14px">
                <button type="button" @click="pos=Math.max(0,pos-10/secs)"><span class="ms" style="font-size:22px">replay_10</span></button>
                <button type="button" @click="pos=Math.min(1,pos+10/secs)"><span class="ms" style="font-size:22px">forward_10</span></button>
                <span class="ms" style="font-size:22px">volume_up</span>
                <span><span x-text="fmt(pos*secs)"></span> / <span x-text="fmt(secs)"></span></span><div class="grow"></div>
                <span style="font-size:11px;font-weight:700;padding:2px 6px;border-radius:5px;border:1px solid rgba(255,255,255,.5)">4K</span><span class="ms" style="font-size:22px">subtitles</span><span class="ms" style="font-size:22px">fullscreen</span>
              </div>
            </div>
          </div>
          <div class="row g6 mt12">
            @foreach (range(0, 7) as $k)
              <div @click="pos={{ $k / 8 + 0.01 }}" class="grow" style="cursor:pointer;aspect-ratio:16/9;border-radius:8px;background:{{ Iw::art($c->art_seed + $k, $k % 5) }}"
                   :style="{ border: '2px solid ' + (Math.floor(pos*8) === {{ $k }} ? '#0A9BFF' : 'transparent'), opacity: Math.floor(pos*8) === {{ $k }} ? 1 : .6 }"></div>
            @endforeach
          </div>
        </div>
      @endif

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
function reviewPage(secs, openReject, oldReasons) {
  return {
    secs, playing: false, pos: 0.28, hx: null, modal: openReject ? 'reject' : null, reasons: oldReasons, feature: '',
    title: @js(old('title', $c->title)), genre: @js(old('genre', $c->genre)), mood: @js(old('mood', $c->mood ?? '')), explicit: {{ $c->explicit ? 'true' : 'false' }},
    fmt: window.iwFmt,
    init() { setInterval(() => { if (this.playing) this.pos = this.pos >= 1 ? 0 : this.pos + 0.005; }, 400); },
    seek(e) { const r = e.currentTarget.getBoundingClientRect(); this.pos = Math.min(1, Math.max(0, (e.clientX - r.left) / r.width)); },
    hover(e) { const r = e.currentTarget.getBoundingClientRect(); this.hx = Math.min(1, Math.max(0, (e.clientX - r.left) / r.width)); },
    toggleReason(r) { this.reasons = this.reasons.includes(r) ? this.reasons.filter(x => x !== r) : [...this.reasons, r]; },
    key(e) {
      if (/INPUT|TEXTAREA|SELECT/.test(e.target.tagName) || e.metaKey || e.ctrlKey || this.modal) return;
      const k = e.key.toLowerCase();
      if (k === 'a') document.getElementById('approveForm').submit();
      else if (k === 'r') this.modal = 'reject';
      else if (k === 'j') this.$refs.next.click();
      else if (k === 'k') this.$refs.prev.click();
    },
  };
}
</script>
@endpush
