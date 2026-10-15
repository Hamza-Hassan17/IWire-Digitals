@extends('layouts.admin')
@section('title', 'Home Curation')

@php $films = $tab === 'Films'; @endphp

@section('content')
<div class="page" x-data="curation(@js($state), @js($pool), {{ $films ? 'true' : 'false' }}, {{ $hasDraft ? 'true' : 'false' }})" @keydown.escape.window="picker=null">
  <form method="POST" action="{{ route('curation.save') }}" x-ref="form" class="hide">
    @csrf
    <input type="hidden" name="tab" value="{{ $tab }}">
    <input type="hidden" name="mode" x-ref="mode">
    <input type="hidden" name="payload" x-ref="payload">
  </form>

  <div class="page-head">
    <div>
      <h1 class="h1">Home Curation</h1>
      <div class="sub row g8">
        <span x-show="dirty">Unsaved changes — preview updates live</span>
        <span x-show="!dirty && hasDraft" x-cloak>Draft saved · not yet published to the app</span>
        <span x-show="!dirty && !hasDraft">
          @if ($publishedAt) Published {{ \Illuminate\Support\Carbon::parse($publishedAt)->diffForHumans() }} · in sync with the app @else In sync with the app @endif
        </span>
        <span x-show="dirty || hasDraft" x-cloak style="width:7px;height:7px;border-radius:50%;background:#F5B041"></span>
      </div>
    </div>
    <div class="row">
      <div class="seg">
        <a href="{{ route('curation.index', ['tab' => 'Music']) }}" class="{{ ! $films ? 'on' : '' }}" @click="if (dirty && !confirm('Discard unsaved changes?')) $event.preventDefault()"><span class="ms">graphic_eq</span>Music home</a>
        <a href="{{ route('curation.index', ['tab' => 'Films']) }}" class="{{ $films ? 'on' : '' }}" @click="if (dirty && !confirm('Discard unsaved changes?')) $event.preventDefault()"><span class="ms">movie</span>Films home</a>
      </div>
      <button type="button" class="btn" @click="submit('draft')">Save draft</button>
      <button type="button" class="btn btn-primary" @click="submit('publish')"><span class="ms">rocket_launch</span>Publish to app</button>
    </div>
  </div>

  <div class="split-cur">
    <div class="col g16" style="min-width:0">
      @if ($films)
        <div class="card tight col g14">
          <div class="row"><span class="ms blue" style="font-size:22px">view_carousel</span><div class="card-title grow">Hero banner carousel</div><span class="muted" style="font-size:12.5px"><span x-text="hero.length"></span> slides · auto-rotates every 6s</span></div>
          <div class="hscroll">
            <template x-for="(h, i) in hero" :key="i + ':' + h.id">
              <div @click="heroSel=i" draggable="true" @dragstart="hd=i" @dragover.prevent @drop.prevent="dropHero(i)"
                   style="cursor:pointer;flex:none;width:220px;border-radius:18px;background:rgba(5,11,22,.35);padding:6px;display:flex;flex-direction:column;gap:8px"
                   :style="{ border: '2px solid ' + (i === heroSel ? '#0A9BFF' : 'transparent') }">
                <div style="aspect-ratio:16/9;border-radius:12px;position:relative" :style="{ background: item(h.id).art }">
                  <span class="avatar" style="position:absolute;top:6px;left:6px;font-size:11px;font-weight:800;width:22px;height:22px;background:rgba(5,11,22,.7)" x-text="i+1"></span>
                  <span class="ms" style="position:absolute;top:6px;right:6px;font-size:18px;color:rgba(255,255,255,.8);cursor:grab">drag_indicator</span>
                </div>
                <div style="padding:0 4px 4px"><div class="b ell" style="font-size:13.5px" x-text="item(h.id).title"></div><div class="fs12 muted ell" x-text="h.tagline"></div></div>
              </div>
            </template>
            <button type="button" class="dashed-add" style="width:150px;border-radius:18px;font-size:13px;gap:6px" @click="addHero()"><span class="ms" style="font-size:26px">add</span>Add slide</button>
          </div>
          <template x-if="hero[heroSel]">
            <div class="panel" style="display:grid;grid-template-columns:200px minmax(0,1fr);gap:16px;padding:16px;border-radius:18px">
              <div style="aspect-ratio:16/9;border-radius:14px;position:relative;display:flex;align-items:flex-end;justify-content:center;padding:8px" :style="{ background: item(hero[heroSel].id).art }">
                <button type="button" class="row g4" style="height:28px;padding:0 10px;border-radius:14px;background:rgba(5,11,22,.75);font-size:11.5px;font-weight:600" @click="$store.toast.show('Banner art uploads are handled by the media pipeline — using the film’s key art', 'info')"><span class="ms" style="font-size:15px">upload</span>Upload banner art</button>
              </div>
              <div class="col g8">
                <label class="field"><span>Film</span>
                  <select class="input" style="height:40px;border-radius:12px;font-size:14px" x-model.number="hero[heroSel].id" @change="dirty=true">
                    <template x-for="p in poolList" :key="p.id"><option :value="p.id" x-text="p.title" :selected="p.id == hero[heroSel].id"></option></template>
                  </select></label>
                <label class="field"><span>Tagline</span><input class="input" style="height:40px;border-radius:12px;font-size:14px" x-model="hero[heroSel].tagline" @input="dirty=true" maxlength="140"></label>
                <button type="button" class="row g4 red" style="align-self:flex-start;font-size:12.5px;font-weight:600" @click="removeHero()"><span class="ms" style="font-size:16px">delete</span>Remove slide</button>
              </div>
            </div>
          </template>
        </div>
      @endif

      <template x-for="(r, ri) in rows" :key="ri">
        <div class="card col g14" style="padding:18px 20px">
          <div class="row">
            <span class="ms faint">drag_indicator</span>
            <input x-model="r.name" @input="dirty=true" class="grow" style="background:transparent;border:1px solid transparent;border-radius:10px;padding:6px 8px;font-size:17px;font-weight:700;color:#fff;outline:none" onfocus="this.style.borderColor='#0A9BFF';this.style.background='rgba(5,11,22,.4)'" onblur="this.style.borderColor='transparent';this.style.background='transparent'">
            <span class="muted nowrap" style="font-size:12.5px"><span x-text="r.ids.length"></span> items</span>
            <button type="button" title="Move up" class="icon-btn xs" @click="move(ri,-1)"><span class="ms">arrow_upward</span></button>
            <button type="button" title="Move down" class="icon-btn xs" @click="move(ri,1)"><span class="ms">arrow_downward</span></button>
            <button type="button" title="Remove row" class="icon-btn xs danger" @click="rows.splice(ri,1); dirty=true"><span class="ms">delete</span></button>
          </div>
          <div class="hscroll" style="padding-bottom:4px">
            <template x-for="(id, i) in r.ids" :key="id">
              <div draggable="true" @dragstart="drag={ri,i}" @dragover.prevent @drop.prevent="dropItem(ri,i)" class="col g6" style="flex:none;cursor:grab" :style="{ width: tileW(ri) }">
                <div style="border-radius:14px;position:relative;border:1px solid var(--line)" :style="{ aspectRatio: ratio(ri), background: item(id).art }">
                  <span class="avatar" style="position:absolute;top:6px;left:6px;font-size:10.5px;font-weight:800;min-width:20px;height:20px;border-radius:10px;background:rgba(5,11,22,.7)" x-text="i+1"></span>
                  <button type="button" class="avatar" style="position:absolute;top:6px;right:6px;width:22px;height:22px;background:rgba(5,11,22,.75)" @click.stop="r.ids.splice(i,1); dirty=true" onmouseover="this.style.background='#E04848'" onmouseout="this.style.background='rgba(5,11,22,.75)'"><span class="ms" style="font-size:15px">close</span></button>
                  <span class="avatar" style="position:absolute;right:6px;bottom:6px;width:26px;height:26px;background:rgba(5,11,22,.8)"><span class="ms fill" style="font-size:17px">play_arrow</span></span>
                </div>
                <div class="sb ell" style="font-size:12.5px" x-text="item(id).title"></div>
              </div>
            </template>
            <button type="button" class="dashed-add" :style="{ width: tileW(ri), aspectRatio: ratio(ri) }" @click="picker=ri; pq=''; $nextTick(() => $refs.pq.focus())"><span class="ms" style="font-size:24px">add</span>Add</button>
          </div>
        </div>
      </template>
      <button type="button" class="dashed-add" style="height:56px;border-radius:20px;flex-direction:row;gap:8px;font-size:14px;color:var(--text-3)" @click="rows.push({ name: 'New row', ids: [] }); dirty=true"><span class="ms" style="font-size:22px">add_row_below</span>Add row</button>
    </div>

    <div class="col g12" style="position:sticky;top:0;align-items:center">
      <div class="label-xs" style="font-size:12px">Live preview · Android</div>
      <div class="phone">
        <div class="phone-screen">
          <div class="row between" style="height:30px;padding:0 22px;font-size:11.5px;font-weight:600"><span>{{ now()->format('g:i') }}</span><span style="width:10px;height:10px;border-radius:50%;background:#000"></span><span class="row" style="gap:3px"><span class="ms" style="font-size:13px">signal_cellular_alt</span><span class="ms" style="font-size:13px">battery_5_bar</span></span></div>
          <div style="height:calc(100% - 30px);overflow-y:auto;padding:10px 16px 100px">
            <div class="row between" style="margin:6px 0 14px">
              <div style="font-size:24px;font-weight:800">Home</div>
              <div class="row g6">
                <div style="width:26px;height:20px;border-radius:50%;background:var(--blue);display:flex;align-items:center;justify-content:center;gap:1.5px"><i style="width:1.5px;height:5px;background:#04101F"></i><i style="width:1.5px;height:10px;background:#04101F"></i><i style="width:1.5px;height:13px;background:#04101F"></i><i style="width:1.5px;height:7px;background:#04101F"></i><i style="width:1.5px;height:10px;background:#04101F"></i></div>
                <span class="blue" style="font-size:15px;font-weight:800">iWire {{ $tab }}</span>
              </div>
            </div>
            @if ($films)
              <div class="row" style="gap:0;padding:3px;border-radius:18px;background:rgba(15,35,64,.8);border:1px solid rgba(255,255,255,.06);margin-bottom:14px">
                <span class="grow center" style="font-size:11.5px;font-weight:700;padding:7px 0;border-radius:15px;background:#1B3A62">For You</span><span class="grow center t3" style="font-size:11.5px;padding:7px 0">New</span><span class="grow center t3" style="font-size:11.5px;padding:7px 0">Trending</span><span class="grow center t3" style="font-size:11.5px;padding:7px 0">Genre</span>
              </div>
              <template x-if="hero[heroSel]">
                <div style="height:170px;border-radius:20px;position:relative;overflow:hidden;border:1px solid rgba(255,255,255,.1);margin-bottom:18px" :style="{ background: item(hero[heroSel].id).art }">
                  <div style="position:absolute;inset:0;background:linear-gradient(90deg, rgba(0,0,0,.6), rgba(0,0,0,0))"></div>
                  <div style="position:absolute;left:14px;top:28px;right:14px">
                    <div style="font-size:17px;font-weight:800" x-text="item(hero[heroSel].id).title"></div>
                    <div class="t2 ell" style="font-size:11px;margin-top:4px" x-text="hero[heroSel].tagline"></div>
                    <div class="row g8" style="margin-top:14px">
                      <span class="row g4" style="height:28px;padding:0 14px;border-radius:14px;background:var(--blue);font-size:12px;font-weight:700"><span class="ms fill" style="font-size:15px">play_arrow</span>Play</span>
                      <span class="row g4" style="height:28px;padding:0 12px;border-radius:14px;border:1px solid rgba(255,255,255,.4);font-size:12px;font-weight:600"><span class="ms" style="font-size:15px">add</span>My List</span>
                    </div>
                  </div>
                  <div class="row g4" style="position:absolute;right:14px;bottom:10px">
                    <template x-for="(_, i) in hero" :key="i"><span style="height:4px;border-radius:2px" :style="{ width: i === heroSel ? '16px' : '4px', background: i === heroSel ? '#0A9BFF' : 'rgba(255,255,255,.4)' }"></span></template>
                  </div>
                </div>
              </template>
            @endif
            <template x-for="(r, ri) in rows" :key="'p' + ri">
              <div style="margin-bottom:18px">
                <div style="font-size:16px;font-weight:700;margin-bottom:10px" x-text="r.name"></div>
                <div class="row" style="gap:10px;overflow:hidden;align-items:flex-start">
                  <template x-for="id in r.ids" :key="id">
                    <div style="flex:none" :style="{ width: films ? (ri === 0 ? '96px' : '130px') : '130px' }">
                      <div style="border-radius:14px;position:relative;border:1px solid var(--line);overflow:hidden" :style="{ aspectRatio: ratio(ri), background: item(id).art }"><span class="avatar" style="position:absolute;right:6px;bottom:6px;width:22px;height:22px;background:rgba(0,0,0,.75)"><span class="ms fill" style="font-size:14px">play_arrow</span></span></div>
                      <div class="ell" style="font-size:11.5px;font-weight:700;margin-top:6px" x-text="item(id).title"></div>
                      <div x-show="!films" class="ell muted" style="font-size:10.5px" x-text="item(id).creator"></div>
                    </div>
                  </template>
                </div>
              </div>
            </template>
          </div>
          <div class="row" style="position:absolute;left:12px;right:12px;bottom:12px;height:52px;border-radius:26px;background:rgba(8,18,34,.92);border:1px solid var(--line);justify-content:space-around">
            <span style="width:44px;height:34px;border-radius:14px;background:#0B3A66;display:flex;align-items:center;justify-content:center"><span class="ms fill blue">home</span></span>
            <span class="ms t3">search</span><span class="ms t3">{{ $films ? 'explore' : 'album' }}</span><span class="ms t3">video_library</span><span class="ms t3">person</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Content picker --}}
  <div class="backdrop top-align" x-show="picker !== null" x-cloak @click.self="picker=null">
    <div class="modal" style="width:560px;padding:0;gap:0;border-radius:26px">
      <div style="padding:18px 20px 12px" class="card-title">Add to “<span x-text="rows[picker]?.name"></span>”</div>
      <div class="row" style="margin:0 20px 10px;height:44px;border-radius:22px;background:rgba(5,11,22,.45);border:1.5px solid var(--blue);padding:0 14px"><span class="ms blue">search</span><input x-ref="pq" x-model="pq" placeholder="Search live content…" class="grow" style="background:none;border:0;outline:none;font-size:14.5px;color:#fff"></div>
      <div style="max-height:360px;overflow-y:auto;padding:0 10px 12px">
        <template x-for="p in poolList.filter(x => !pq || (x.title + x.creator).toLowerCase().includes(pq.toLowerCase()))" :key="p.id">
          <button type="button" class="pal-item" style="height:auto;padding:8px 10px;border-radius:14px" @click="addTo(p)">
            <div class="thumb" :style="{ width: films ? '30px' : '44px', background: p.art }"></div>
            <div class="grow"><div class="sb fs14" x-text="p.title"></div><div class="muted" style="font-size:12.5px" x-text="`${p.creator} · ${p.genre} · ${p.playsK} plays`"></div></div>
            <span class="ms" style="font-size:22px" :style="{ color: rows[picker]?.ids.includes(p.id) ? '#5FD99A' : '#0A9BFF' }" x-text="rows[picker]?.ids.includes(p.id) ? 'check_circle' : 'add_circle'"></span>
          </button>
        </template>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
function curation(state, pool, films, hasDraft) {
  return {
    rows: state.rows, hero: state.hero || [], pool, films, hasDraft, dirty: false,
    heroSel: 0, picker: null, pq: '', drag: null, hd: null,
    get poolList() { return Object.values(this.pool); },
    item(id) { return this.pool[id] || { title: 'Unavailable', art: '#0F2340', creator: '' }; },
    ratio(ri) { return this.films ? (ri === 0 ? '2/3' : '16/10') : '1'; },
    tileW(ri) { return this.films && ri === 0 ? '110px' : '130px'; },
    move(ri, d) { const j = ri + d; if (j < 0 || j >= this.rows.length) return; [this.rows[ri], this.rows[j]] = [this.rows[j], this.rows[ri]]; this.rows = [...this.rows]; this.dirty = true; },
    dropItem(ri, i) { const d = this.drag; if (!d || d.ri !== ri) return; const ids = this.rows[ri].ids; const [m] = ids.splice(d.i, 1); ids.splice(i, 0, m); this.drag = null; this.dirty = true; },
    dropHero(i) { if (this.hd === null) return; const [m] = this.hero.splice(this.hd, 1); this.hero.splice(i, 0, m); this.heroSel = i; this.hd = null; this.dirty = true; },
    addHero() { const used = this.hero.map(h => h.id); const f = this.poolList.find(p => !used.includes(p.id)) || this.poolList[0]; if (!f) return; this.hero.push({ id: f.id, tagline: 'Add a short tagline' }); this.heroSel = this.hero.length - 1; this.dirty = true; },
    removeHero() { if (this.hero.length <= 1) return this.$store.toast.show('The carousel needs at least one slide', 'info'); this.hero.splice(this.heroSel, 1); this.heroSel = Math.max(0, this.heroSel - 1); this.dirty = true; },
    addTo(p) { const r = this.rows[this.picker]; if (!r || r.ids.includes(p.id)) return; r.ids.push(p.id); this.dirty = true; this.$store.toast.show(`Added “${p.title}” to ${r.name}`, 'ok'); },
    submit(mode) { this.$refs.mode.value = mode; this.$refs.payload.value = JSON.stringify({ rows: this.rows, hero: this.hero }); this.dirty = false; this.$refs.form.submit(); },
    init() { window.addEventListener('beforeunload', e => { if (this.dirty) { e.preventDefault(); e.returnValue = ''; } }); },
  };
}
</script>
@endpush
