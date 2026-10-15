@extends('layouts.admin')
@section('title', 'Genres & Moods')

@php
    use App\Support\Iw;
    $arts = collect(range(0, 11))->flatMap(fn ($s) => collect(range(0, 4))->mapWithKeys(fn ($st) => ["$s-$st" => Iw::art($s, $st)]));
    $items = $list->map(fn ($g) => ['id' => $g->id, 'name' => $g->name, 'color' => $g->color, 'count' => $g->count, 'art_seed' => $g->art_seed, 'art_style' => $g->art_style])->values();
@endphp

@section('content')
<div class="page" x-data="genres(@js($items), @js($arts))" @keydown.escape.window="modal=null">
  <div class="page-head">
    <div><h1 class="h1">Genres & Moods</h1><div class="sub">Order here is the order shown in the app. Drag rows to reorder.</div></div>
    <button type="button" class="btn btn-primary" @click="openAdd()"><span class="ms">add</span>Add {{ $tab === 'moods' ? 'mood' : 'genre' }}</button>
  </div>
  <div class="seg">
    @foreach ([['music', 'Music genres', 'graphic_eq'], ['films', 'Film genres', 'movie'], ['moods', 'Moods', 'mood']] as [$k, $label, $icon])
      <a href="{{ route('genres.index', ['tab' => $k]) }}" class="{{ $tab === $k ? 'on' : '' }}"><span class="ms">{{ $icon }}</span>{{ $label }}<span class="count">{{ $counts[$k] ?? 0 }}</span></a>
    @endforeach
  </div>

  <div class="split-g">
    <div class="card" style="padding:8px">
      <template x-for="(g, i) in list" :key="g.id">
        <div draggable="true" @dragstart="drag=i" @dragover.prevent="over=i" @drop.prevent="drop(i)" @dragend="over=null"
             class="row" style="gap:14px;padding:10px 12px;border-radius:16px;border-top:2px solid transparent" :class="over===i && 'drag-over'">
          <span class="ms faint" style="cursor:grab">drag_indicator</span>
          <span class="num faint" style="width:22px;font-size:12.5px;font-weight:700" x-text="i+1"></span>
          <div style="width:64px;height:48px;border-radius:12px;flex:none;position:relative;overflow:hidden" :style="{ background: art(g) }"><div style="position:absolute;inset:0;mix-blend-mode:color;opacity:.55" :style="{ background: g.color }"></div></div>
          <span style="width:14px;height:14px;border-radius:50%;flex:none;box-shadow:0 0 0 3px rgba(255,255,255,.06)" :style="{ background: g.color }"></span>
          <div class="grow"><div class="b fs15" x-text="g.name"></div><div class="muted" style="font-size:12.5px"><span x-text="g.count"></span> {{ $tab === 'films' ? 'films' : 'tracks' }}</div></div>
          <button type="button" class="icon-btn xs" @click="openEdit(g)"><span class="ms" style="font-size:19px">edit</span></button>
          <form method="POST" :action="`{{ url('genres') }}/${g.id}`" @submit="if (!confirm(`Remove “${g.name}”?`)) $event.preventDefault()">@csrf @method('DELETE')<button class="icon-btn xs danger"><span class="ms" style="font-size:19px">delete</span></button></form>
        </div>
      </template>
      <div x-show="!list.length" class="center muted fs14" style="padding:32px">Nothing here yet.</div>
    </div>
    <div class="card" style="padding:20px">
      <div class="label-xs" style="font-size:12px;margin-bottom:14px">How it looks in the app</div>
      <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px">
        <template x-for="g in list" :key="'p'+g.id">
          <div style="aspect-ratio:16/10;border-radius:16px;position:relative;overflow:hidden;border:1px solid var(--line)" :style="{ background: art(g) }">
            <div style="position:absolute;inset:0;opacity:.75" :style="{ background: `linear-gradient(135deg, ${g.color}, transparent 75%)` }"></div>
            <div style="position:absolute;left:12px;bottom:10px;font-size:15px;font-weight:800" x-text="g.name"></div>
          </div>
        </template>
      </div>
    </div>
  </div>

  <div class="backdrop" x-show="modal" x-cloak @click.self="modal=null">
    <form method="POST" :action="modal==='edit' ? `{{ url('genres') }}/${form.id}` : '{{ route('genres.store') }}'" class="modal" style="width:480px;padding:26px;gap:18px" @submit="if (!form.name.trim()) $event.preventDefault()">
      @csrf
      <template x-if="modal==='edit'"><input type="hidden" name="_method" value="PUT"></template>
      <input type="hidden" name="kind" value="{{ $tab }}">
      <input type="hidden" name="color" :value="form.color"><input type="hidden" name="art_seed" :value="form.art_seed"><input type="hidden" name="art_style" :value="form.art_style">
      <div class="row"><div class="modal-title grow" x-text="modal==='edit' ? `Edit “${form.name}”` : 'New {{ $tab === 'moods' ? 'mood' : 'genre' }}'"></div><button type="button" class="icon-btn xs" @click="modal=null"><span class="ms">close</span></button></div>
      <div style="aspect-ratio:16/7;border-radius:18px;position:relative;overflow:hidden;display:flex;align-items:flex-end;justify-content:space-between;padding:14px" :style="{ background: art(form) }">
        <div style="position:absolute;inset:0;opacity:.75" :style="{ background: `linear-gradient(135deg, ${form.color}, transparent 75%)` }"></div>
        <div style="position:relative;font-size:22px;font-weight:800" x-text="form.name || 'Name'"></div>
        <button type="button" class="row g6" style="position:relative;height:32px;padding:0 12px;border-radius:16px;background:rgba(5,11,22,.7);font-size:12.5px;font-weight:600" @click="form.art_seed=Math.floor(Math.random()*12); form.art_style=Math.floor(Math.random()*5)"><span class="ms" style="font-size:16px">shuffle</span>New cover</button>
      </div>
      <label class="field"><span>Name</span><input class="input" name="name" x-model="form.name" placeholder="e.g. Afro-house" maxlength="40" required></label>
      <div><div class="fs12 sb muted" style="margin-bottom:8px">Color</div>
        <div class="row">@foreach ($swatches as $c)<button type="button" style="width:34px;height:34px;border-radius:50%;background:{{ $c }}" :style="{ boxShadow: form.color === '{{ $c }}' ? '0 0 0 3px #0E2240, 0 0 0 5px #fff' : 'none' }" @click="form.color='{{ $c }}'"></button>@endforeach</div></div>
      <div class="row end"><button type="button" class="btn btn-md btn-text" @click="modal=null">Cancel</button><button class="btn btn-md btn-primary" style="box-shadow:none">Save</button></div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
function genres(list, arts) {
  return {
    list, arts, drag: null, over: null, modal: null, form: { name: '', color: '#0A9BFF', art_seed: 0, art_style: 0 },
    art(g) { return this.arts[g.art_seed + '-' + g.art_style]; },
    openAdd() { this.form = { name: '', color: '#0A9BFF', art_seed: (this.list.length + 3) % 12, art_style: this.list.length % 5 }; this.modal = 'add'; },
    openEdit(g) { this.form = { ...g }; this.modal = 'edit'; },
    drop(i) {
      if (this.drag === null) return;
      const [m] = this.list.splice(this.drag, 1); this.list.splice(i, 0, m); this.drag = null; this.over = null;
      fetch(@js(route('genres.reorder')), { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': iwCsrf() }, body: JSON.stringify({ ids: this.list.map(g => g.id) }) })
        .then(r => this.$store.toast.show(r.ok ? 'Order updated' : 'Could not save order', r.ok ? 'info' : 'bad'));
    },
  };
}
</script>
@endpush
