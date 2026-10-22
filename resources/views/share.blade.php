<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
@if ($c)
  @php $by = $c->creator->name; $desc = \Illuminate\Support\Str::limit((string) $c->description, 150) ?: ($c->is_music ? "A track by $by on iWire" : "A film by $by on iWire"); @endphp
  <title>{{ $c->title }} · {{ $by }} · iWire</title>
  <meta name="description" content="{{ $desc }}">
  <meta property="og:type" content="{{ $c->is_music ? 'music.song' : 'video.movie' }}">
  <meta property="og:site_name" content="iWire">
  <meta property="og:title" content="{{ $c->title }} — {{ $by }}">
  <meta property="og:description" content="{{ $desc }}">
  <meta property="og:image" content="{{ $c->is_music ? $c->cover_url : $c->banner_url }}">
  <meta property="og:url" content="{{ $c->share_url }}">
  <meta name="twitter:card" content="summary_large_image">
@else
  <title>Not available · iWire</title>
@endif
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap">
<style>
  body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; box-sizing: border-box;
         font-family: Inter, system-ui, sans-serif; color: #fff; background: radial-gradient(700px 500px at 0% 0%, rgba(10,155,255,.22), transparent 60%), linear-gradient(180deg,#0B1E3A,#050B16); }
  .card { width: 100%; max-width: 380px; text-align: center; }
  .cover { width: 100%; border-radius: 22px; box-shadow: 0 24px 60px rgba(0,0,0,.55); background-size: cover; background-position: center; }
  h1 { font-size: 26px; font-weight: 800; margin: 22px 0 4px; letter-spacing: -.02em; }
  .by { color: #8FA3BF; font-size: 15px; }
  .btn { display: block; margin-top: 22px; padding: 15px; border-radius: 26px; background: #0A9BFF; color: #fff; font-weight: 700; text-decoration: none; }
  .alt { display: block; margin-top: 12px; color: #8FD0FF; font-size: 14px; text-decoration: none; }
  .brand { margin-top: 28px; color: #5E7290; font-size: 13px; }
</style>
</head>
<body>
<div class="card">
  @if ($c)
    <div class="cover" style="aspect-ratio:{{ $c->is_music ? '1' : '2/3' }};background-image:url('{{ $c->cover_url }}')"></div>
    <h1>{{ $c->title }}</h1>
    <div class="by">{{ $c->is_music ? 'Track' : 'Film' }} · {{ $c->creator->name }} · {{ $c->genre }}</div>
    <a class="btn" href="{{ $c->app_link }}">Open in iWire</a>
    @if ($store)<a class="alt" href="{{ $store }}">Get the iWire app</a>@endif
  @else
    <h1>This isn’t available</h1>
    <div class="by">It may have been removed or isn’t published yet.</div>
  @endif
  <div class="brand">iWire Music & Films</div>
</div>
</body>
</html>
