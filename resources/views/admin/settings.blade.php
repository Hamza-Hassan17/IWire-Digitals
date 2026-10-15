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
