@extends('layouts.admin')
@section('title', 'Settings')

@section('content')
<div class="page" style="max-width:880px">
  <div class="page-head"><div><h1 class="h1">Settings</h1><div class="sub">Your admin account and platform rules.</div></div></div>
  @if ($errors->any())<div class="panel err">{{ $errors->first() }}</div>@endif

  <form method="POST" action="{{ route('settings.profile') }}" class="card col g16">
    @csrf @method('PUT')
    <div class="card-title">Profile</div>
    <div class="fields">
      <label class="field"><span>Name</span><input class="input" name="name" value="{{ old('name', auth()->user()->name) }}" required></label>
      <label class="field"><span>Email</span><input class="input" type="email" name="email" value="{{ old('email', auth()->user()->email) }}" required></label>
    </div>
    <div class="row end"><button class="btn btn-sm btn-primary" style="box-shadow:none">Save profile</button></div>
  </form>

  <form method="POST" action="{{ route('settings.password') }}" class="card col g16">
    @csrf @method('PUT')
    <div class="card-title">Password</div>
    <div class="fields">
      <label class="field"><span>Current password</span><input class="input" type="password" name="current_password" required autocomplete="current-password"></label>
      <label class="field"><span>New password</span><input class="input" type="password" name="password" required minlength="10" autocomplete="new-password"></label>
      <label class="field"><span>Confirm new password</span><input class="input" type="password" name="password_confirmation" required autocomplete="new-password"></label>
    </div>
    <div class="row end"><button class="btn btn-sm btn-primary" style="box-shadow:none">Change password</button></div>
  </form>

  <form method="POST" action="{{ route('settings.app') }}" enctype="multipart/form-data" class="card col g16" x-data="{ mm: {{ $app['maintenance_mode'] ? 'true' : 'false' }} }">
    @csrf
    <input type="hidden" name="maintenance_mode" :value="mm ? 1 : 0">
    <div><div class="card-title">Mobile app</div><div class="card-sub">Served to the app on launch by <code>GET /api/v1/app/config</code>.</div></div>
    <div class="panel row between">
      <div><div class="sb fs14">Maintenance mode</div><div class="muted" style="font-size:12.5px;margin-top:2px">The app shows the message below instead of loading content</div></div>
      <button type="button" class="switch" :class="mm && 'on'" @click="mm=!mm"></button>
    </div>
    <label class="field" x-show="mm" x-cloak><span>Maintenance message</span><input class="input" name="maintenance_message" value="{{ old('maintenance_message', $app['maintenance_message']) }}" maxlength="300" placeholder="We’re upgrading iWire — back in 30 minutes."></label>
    <div class="fields">
      <label class="field"><span>Minimum app version</span><input class="input" name="min_app_version" value="{{ old('min_app_version', $app['min_app_version']) }}" placeholder="1.0.0" required></label>
      <label class="field"><span>Support email</span><input class="input" type="email" name="support_email" value="{{ old('support_email', $app['support_email']) }}" required></label>
      <label class="field"><span>Google Play link <span class="faint">(shown on share pages)</span></span><input class="input" type="url" name="android_store_url" value="{{ old('android_store_url', $app['android_store_url']) }}" placeholder="https://play.google.com/store/apps/details?id=…"></label>
    </div>
    <div class="fields">
      @foreach (['music' => 'studio-music.png', 'films' => 'studio-films.png'] as $k => $fallback)
        @php $img = $app["splash_{$k}_image"] ? \Illuminate\Support\Facades\Storage::disk('public')->url($app["splash_{$k}_image"]) : asset("images/$fallback"); @endphp
        <div class="col g8">
          <div style="aspect-ratio:16/9;border-radius:14px;background:url('{{ $img }}') center/cover no-repeat, #0F2340;border:1px solid var(--line)"></div>
          <label class="field"><span>{{ ucfirst($k) }} splash title</span><input class="input" name="splash_{{ $k }}_title" value="{{ old("splash_{$k}_title", $app["splash_{$k}_title"]) }}" required maxlength="60"></label>
          <label class="field"><span>{{ ucfirst($k) }} splash image</span><input class="input" style="padding-top:10px" type="file" name="splash_{{ $k }}_image" accept="image/jpeg,image/png,image/webp"></label>
        </div>
      @endforeach
    </div>
    <div class="row end"><button class="btn btn-sm btn-primary" style="box-shadow:none">Save app settings</button></div>
  </form>

  <form method="POST" action="{{ route('settings.pages') }}" class="card col g16">
    @csrf @method('PUT')
    <div><div class="card-title">Terms & Privacy</div><div class="card-sub">Shown in the app by <code>GET /api/v1/pages/terms</code> and <code>/pages/privacy</code>. Plain text; blank lines start a new paragraph.</div></div>
    @foreach (['terms' => $terms, 'privacy' => $privacy] as $slug => $page)
      <label class="field"><span>{{ $page['title'] }} @if ($page['updated_at'])<span class="faint">· updated {{ \Illuminate\Support\Carbon::parse($page['updated_at'])->diffForHumans() }}</span>@endif</span>
        <textarea class="input" name="{{ $slug }}" rows="8" placeholder="Paste your {{ strtolower($page['title']) }} here…">{{ old($slug, $page['content']) }}</textarea></label>
    @endforeach
    <div class="row end"><button class="btn btn-sm btn-primary" style="box-shadow:none">Save terms & privacy</button></div>
  </form>

  <form method="POST" action="{{ route('settings.platform') }}" class="card col g16" x-data="{ ex: {{ $platform['require_explicit_label'] ? 'true' : 'false' }} }">
    @csrf @method('PUT')
    <input type="hidden" name="require_explicit_label" :value="ex ? 1 : 0">
    <div class="card-title">Platform rules</div>
    <div class="fields">
      <label class="field"><span>Auto-flag after N reports</span><input class="input" type="number" name="auto_flag_threshold" value="{{ $platform['auto_flag_threshold'] }}" min="1"></label>
      <label class="field"><span>Max film upload (GB)</span><input class="input" type="number" name="max_film_gb" value="{{ $platform['max_film_gb'] }}" min="1"></label>
      <label class="field"><span>Max track length (min)</span><input class="input" type="number" name="max_track_minutes" value="{{ $platform['max_track_minutes'] }}" min="1"></label>
    </div>
    <div class="panel row between">
      <div><div class="sb fs14">Require explicit labelling</div><div class="muted" style="font-size:12.5px;margin-top:2px">Creators must declare explicit content on upload</div></div>
      <button type="button" class="switch" :class="ex && 'on'" @click="ex=!ex"></button>
    </div>
    <div class="row end"><button class="btn btn-sm btn-primary" style="box-shadow:none">Save rules</button></div>
  </form>
</div>
@endsection
