@extends('layouts.admin')
@section('title', 'Push Notifications')

@section('content')
<div class="page" x-data="{ title: @js(old('title', '')), body: @js(old('body', '')), when: @js(old('when', 'now')) }">
  <div class="page-head"><div><h1 class="h1">Push Notifications</h1><div class="sub">Reach listeners and creators on their phones.</div></div></div>

  <div class="split-g">
    <form method="POST" action="{{ route('push.store') }}" class="card col g16">
      @csrf
      <div class="card-title">Compose</div>
      @if ($errors->any())<div class="err">{{ $errors->first() }}</div>@endif
      <label class="field"><span>Title <span class="faint" x-text="`${title.length}/65`"></span></span><input class="input" name="title" x-model="title" maxlength="65" required placeholder="New drop from…"></label>
      <label class="field"><span>Message <span class="faint" x-text="`${body.length}/178`"></span></span><textarea class="input" name="body" x-model="body" rows="3" maxlength="178" required placeholder="Tap to listen."></textarea></label>
      <div class="fields">
        <label class="field"><span>Audience</span><select class="input" name="audience">@foreach ($audiences as $a)<option @selected(old('audience') === $a)>{{ $a }}</option>@endforeach</select></label>
        <label class="field"><span>When</span><select class="input" name="when" x-model="when"><option value="now">Send now</option><option value="later">Schedule</option></select></label>
        <label class="field" x-show="when==='later'" x-cloak><span>Send at</span><input class="input" type="datetime-local" name="send_at" value="{{ old('send_at') }}" style="color-scheme:dark"></label>
      </div>
      <div class="row end"><button class="btn btn-primary"><span class="ms">send</span><span x-text="when==='later' ? 'Schedule' : 'Send notification'"></span></button></div>
    </form>

    <div class="col g12" style="align-items:center">
      <div class="label-xs" style="font-size:12px">Lock-screen preview</div>
      <div style="width:100%;max-width:360px;border-radius:22px;padding:14px 16px;background:rgba(255,255,255,.1);backdrop-filter:blur(20px);border:1px solid var(--line-strong)">
        <div class="row g8 fs12 muted"><div class="logo" style="width:20px;height:20px;gap:1px"><i style="height:5px;width:1.5px"></i><i style="height:9px;width:1.5px"></i><i style="height:12px;width:1.5px"></i><i style="height:7px;width:1.5px"></i></div>iWire<span class="grow"></span>now</div>
        <div class="b fs14 mt8" x-text="title || 'Notification title'"></div>
        <div class="t2 fs13 mt4" style="line-height:1.4" x-text="body || 'Your message appears here.'"></div>
      </div>
    </div>
  </div>

  <div class="card flush r22 table-wrap">
    <table class="tbl" style="min-width:820px">
      <thead><tr><th>Notification</th><th>Audience</th><th>Time</th><th class="right">Reach</th><th class="right">CTR</th><th>Status</th></tr></thead>
      <tbody>
        @foreach ($sent as $n)
          <tr>
            <td><div class="sb">{{ $n->title }}</div><div class="muted ell" style="font-size:12.5px;max-width:420px">{{ $n->body }}</div></td>
            <td class="t2">{{ $n->audience }}</td>
            <td class="t3 nowrap">{{ $n->send_at?->isToday() ? 'Today, '.$n->send_at->format('H:i') : $n->send_at?->format('M j, H:i') }}</td>
            <td class="right num sb">{{ $n->reach ? \App\Support\Iw::k($n->reach) : '—' }}</td>
            <td class="right num t2">{{ $n->ctr ? $n->ctr.'%' : '—' }}</td>
            <td>@include('admin.partials.status', ['s' => $n->status])</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endsection
