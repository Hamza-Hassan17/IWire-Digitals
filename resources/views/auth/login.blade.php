<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sign in · iWire Control Room</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0..1,0&display=swap">
<link rel="stylesheet" href="{{ asset('css/iwire.css') }}">
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
<style>
  .login-art { flex: 1.25; position: relative; display: flex; flex-direction: column; min-width: 0; overflow: hidden; }
  .login-half { flex: 1; position: relative; background-color: #03070F; background-repeat: no-repeat; background-size: cover; }
  .login-cap { position: absolute; left: 48px; display: flex; align-items: center; gap: 12px; font-size: 22px; font-weight: 700; letter-spacing: -.01em; }
  @media (max-width: 900px) { .login-art { display: none; } }
</style>
</head>
<body>
<div style="display:flex;min-height:100vh;background:var(--bg)">
  <div class="login-art">
    <div class="login-half" style="background-image:url('{{ asset('images/studio-music.png') }}');background-position:center 30%">
      <div style="position:absolute;inset:0;background:linear-gradient(90deg, rgba(5,11,22,.15), rgba(5,11,22,.55)), linear-gradient(180deg, rgba(5,11,22,0) 50%, rgba(5,11,22,.7))"></div>
      <div class="login-cap" style="bottom:32px"><span class="ms blue" style="font-size:22px">graphic_eq</span><span>Run <span class="blue">Music</span></span></div>
    </div>
    <div style="height:2px;background:linear-gradient(90deg, rgba(255,255,255,0), rgba(255,255,255,.6), rgba(255,255,255,0))"></div>
    <div class="login-half" style="background-image:url('{{ asset('images/studio-films.png') }}');background-position:center 20%">
      <div style="position:absolute;inset:0;background:linear-gradient(90deg, rgba(5,11,22,.15), rgba(5,11,22,.55)), linear-gradient(0deg, rgba(5,11,22,0) 50%, rgba(5,11,22,.6))"></div>
      <div class="login-cap" style="top:32px"><span class="ms blue" style="font-size:22px">movie</span><span>Run <span class="blue">Films</span></span></div>
      <div class="muted fs13" style="position:absolute;left:48px;right:48px;bottom:40px">© {{ date('Y') }} iWire Media · Internal use only</div>
    </div>
  </div>

  <div style="flex:1;min-width:min(420px,100%);display:flex;align-items:center;justify-content:center;padding:48px 24px;background:radial-gradient(700px 500px at 0% 0%, rgba(10,155,255,.18), transparent 60%), linear-gradient(180deg,#0B1E3A,#050B16)">
    <form method="POST" action="{{ route('login') }}" x-data="{ show: false, remember: true }" style="width:100%;max-width:400px;display:flex;flex-direction:column;gap:28px">
      @csrf
      <div class="row g12">
        <div class="logo lg"><i style="height:10px"></i><i style="height:19px"></i><i style="height:26px"></i><i style="height:14px"></i><i style="height:21px"></i><i style="height:10px"></i></div>
        <div style="font-weight:800;font-size:24px;letter-spacing:-.02em">iWire <span class="blue" style="font-weight:700">Control Room</span></div>
      </div>
      <div>
        <h1 style="font-size:34px;font-weight:800;letter-spacing:-.025em;line-height:1.15">Welcome back</h1>
        <p class="muted" style="margin:10px 0 0;font-size:15px;line-height:1.5">Sign in to review uploads, curate the app and keep iWire running.</p>
      </div>
      <div class="col g16">
        <label class="col g8">
          <span class="sb fs13 t2">Email</span>
          <div class="login-in"><span class="ms faint">mail</span><input type="email" name="email" value="{{ old('email', 'admin@iwire.app') }}" required autocomplete="username"></div>
        </label>
        <label class="col g8">
          <div class="row between"><span class="sb fs13 t2">Password</span><a href="#" class="sb fs13" @click.prevent="alert('Contact the platform owner to reset the admin password.')">Forgot password?</a></div>
          <div class="login-in">
            <span class="ms faint">lock</span>
            <input :type="show ? 'text' : 'password'" name="password" required autofocus autocomplete="current-password" style="letter-spacing:.1em">
            <button type="button" @click="show=!show" class="muted" style="display:flex"><span class="ms" x-text="show ? 'visibility_off' : 'visibility'">visibility</span></button>
          </div>
        </label>
        @error('email')<div class="err">{{ $message }}</div>@enderror
        <label class="row" style="gap:10px;font-size:13.5px;color:var(--text-2);cursor:pointer" @click.prevent="remember=!remember">
          <span class="cb" :class="remember && 'on'" style="border-radius:6px"><span class="ms" style="font-size:16px">check</span></span>Keep me signed in on this device
          <input type="hidden" name="remember" :value="remember ? 1 : 0">
        </label>
      </div>
      <button class="btn btn-primary" style="height:52px;border-radius:26px;font-size:15.5px;box-shadow:0 10px 30px rgba(10,155,255,.35)">Sign in<span class="ms">arrow_forward</span></button>
      <div class="row faint" style="font-size:12.5px"><span class="ms" style="font-size:17px">shield_lock</span>Single admin account · Protected by 2-step verification</div>
    </form>
  </div>
</div>
</body>
</html>
