<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\Genre;
use App\Models\Listener;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MobileApiTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function token(string $email = 'listener@iwire.app', string $password = 'listener123'): string
    {
        return $this->postJson('/api/v1/auth/login', compact('email', 'password'))->json('data.token');
    }

    public function test_register_login_me_logout(): void
    {
        $r = $this->postJson('/api/v1/auth/register', ['name' => 'Ali Khan', 'email' => 'ali@mail.com', 'password' => 'secret123', 'password_confirmation' => 'secret123', 'account_type' => 'listener'])
            ->assertCreated()
            ->assertJsonPath('success', true)->assertJsonPath('message', 'Registered')
            ->assertJsonPath('data.user.email', 'ali@mail.com')->assertJsonPath('data.user.account_type', 'listener')->assertJsonPath('data.user.avatar', null)
            ->assertJsonStructure(['data' => ['token', 'user' => ['id', 'name', 'email', 'account_type', 'avatar']]]);
        $this->assertDatabaseHas('listeners', ['email' => 'ali@mail.com']);

        $this->postJson('/api/v1/auth/register', ['name' => 'Ali', 'email' => 'ali@mail.com', 'password' => 'secret123', 'password_confirmation' => 'secret123', 'account_type' => 'listener'])
            ->assertStatus(422)->assertJsonPath('success', false)->assertJsonPath('message', 'Validation failed')
            ->assertJsonPath('errors.email.0', 'The email has already been taken.');

        $this->postJson('/api/v1/auth/register', ['name' => 'Studio', 'email' => 'studio@mail.com', 'password' => 'secret123', 'password_confirmation' => 'secret123', 'account_type' => 'creator'])->assertCreated();
        $this->assertDatabaseHas('creators', ['email' => 'studio@mail.com']);

        $this->postJson('/api/v1/auth/login', ['email' => 'ali@mail.com', 'password' => 'nope'])->assertStatus(401)->assertExactJson(['success' => false, 'message' => 'Invalid credentials']);

        $token = $this->postJson('/api/v1/auth/login', ['email' => 'ali@mail.com', 'password' => 'secret123'])->assertOk()->json('data.token');
        $this->withToken($token)->getJson('/api/v1/auth/me')->assertOk()->assertJsonPath('data.user.email', 'ali@mail.com');
        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk()->assertJsonPath('message', 'Logged out');
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/v1/auth/me')->assertStatus(401)->assertJsonPath('message', 'Unauthenticated');
    }

    public function test_protected_routes_need_token_even_without_accept_header(): void
    {
        $this->get('/api/v1/music/home')->assertStatus(401)->assertJsonPath('success', false);
    }

    public function test_app_config_is_public(): void
    {
        $this->getJson('/api/v1/app/config')->assertOk()
            ->assertJsonPath('data.maintenance_mode', false)->assertJsonPath('data.min_app_version', '1.0.0')
            ->assertJsonPath('data.splash.music.title', 'Slide Down For Music')->assertJsonPath('data.splash.films.title', 'Slide Up For Films')
            ->assertJsonStructure(['data' => ['maintenance_message', 'support_email', 'splash' => ['music' => ['image'], 'films' => ['image']]]]);
    }

    public function test_music_endpoints(): void
    {
        $t = $this->token();
        $home = $this->withToken($t)->getJson('/api/v1/music/home')->assertOk()
            ->assertJsonPath('data.sections.0.key', 'find_your_mood')->assertJsonPath('data.sections.1.key', 'top_picks')
            ->assertJsonStructure(['data' => ['sections' => [['key', 'title', 'items' => [['id', 'title', 'subtitle', 'cover', 'artist']]]]]]);

        $id = $home->json('data.sections.0.items.0.id');
        $this->withToken($t)->getJson("/api/v1/music/tracks/$id")->assertOk()
            ->assertJsonStructure(['data' => ['id', 'title', 'description', 'artist', 'cover', 'genre', 'duration', 'stream_url']])
            ->assertJsonPath('data.stream_url', null);
        $this->assertIsInt($this->withToken($t)->getJson("/api/v1/music/tracks/$id")->json('data.duration'));

        $pending = Content::pending()->where('type', 'Music')->first();
        $this->withToken($t)->getJson("/api/v1/music/tracks/{$pending->id}")->assertNotFound()->assertJsonPath('message', 'Not found');
        $film = Content::where('type', 'Films')->where('status', 'Live')->first();
        $this->withToken($t)->getJson("/api/v1/music/tracks/{$film->id}")->assertNotFound();
    }

    public function test_film_endpoints(): void
    {
        $t = $this->token();
        foreach (['for_you', 'new', 'trending'] as $tab) {
            $this->withToken($t)->getJson("/api/v1/films/home?tab=$tab")->assertOk()
                ->assertJsonStructure(['data' => ['banners' => [['id', 'title', 'subtitle', 'image']], 'sections' => [['key', 'title', 'items' => [['id', 'title', 'poster', 'genre']]]]]]);
        }
        $this->withToken($t)->getJson('/api/v1/films/home?tab=bogus')->assertStatus(422);

        $genres = $this->withToken($t)->getJson('/api/v1/films/genres')->assertOk()->assertJsonStructure(['data' => [['id', 'name']]])->json('data');
        $drama = collect($genres)->firstWhere('name', 'Drama');
        $this->withToken($t)->getJson("/api/v1/films/genres/{$drama['id']}")->assertOk()
            ->assertJsonStructure(['data' => [['id', 'title', 'poster', 'genre']], 'meta' => ['current_page', 'last_page', 'per_page', 'total']])
            ->assertJsonPath('meta.current_page', 1);
        $musicGenre = Genre::where('kind', 'music')->first();
        $this->withToken($t)->getJson("/api/v1/films/genres/{$musicGenre->id}")->assertNotFound();

        $film = Content::where('type', 'Films')->where('status', 'Live')->first();
        $this->withToken($t)->getJson("/api/v1/films/{$film->id}")->assertOk()
            ->assertJsonStructure(['data' => ['id', 'title', 'description', 'poster', 'banner', 'genre', 'duration', 'creator', 'stream_url']]);
    }

    public function test_suspended_account_is_blocked(): void
    {
        $t = $this->token();
        Listener::where('email', 'listener@iwire.app')->update(['status' => 'Suspended']);
        $this->withToken($t)->getJson('/api/v1/music/home')->assertForbidden()->assertJsonPath('success', false);
        $this->postJson('/api/v1/auth/login', ['email' => 'listener@iwire.app', 'password' => 'listener123'])->assertForbidden();
    }

    public function test_uploaded_media_streams_through_signed_url(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $this->actingAs(User::where('account_type', 'admin')->first());
        $track = Content::where('type', 'Music')->where('status', 'Live')->first();

        $this->post("/library/{$track->id}/media", [
            'cover' => UploadedFile::fake()->image('cover.jpg', 600, 600),
            'file' => UploadedFile::fake()->create('song.mp3', 200, 'audio/mpeg'),
        ])->assertRedirect()->assertSessionHasNoErrors();
        $track->refresh();
        $this->assertNotNull($track->file_path);

        $data = $this->withToken($this->token())->getJson("/api/v1/music/tracks/{$track->id}")->json('data');
        $this->assertStringContainsString('/storage/artwork/', $data['cover']);
        $this->assertStringContainsString('signature=', $data['stream_url']);

        $plays = $track->plays;
        $this->get($data['stream_url'])->assertOk();
        $this->assertSame($plays + 1, $track->fresh()->plays);
        $this->getJson("/api/v1/stream/{$track->id}")->assertForbidden()->assertJsonPath('message', 'Link expired or invalid');
    }

    public function test_generated_art_is_a_png(): void
    {
        Storage::fake('local');
        $r = $this->get('/media/art/600x600/3-1.png')->assertOk();
        $this->assertSame('image/png', $r->headers->get('Content-Type'));
        $this->get('/media/art/999x999/3-1.png')->assertNotFound();
        $this->get('/media/art/600x600/40-1.png')->assertNotFound();
    }

    public function test_app_users_cannot_enter_control_room(): void
    {
        $this->post('/login', ['email' => 'listener@iwire.app', 'password' => 'listener123'])->assertSessionHasErrors('email');
        $this->actingAs(User::where('email', 'listener@iwire.app')->first())->get('/')->assertRedirect('/login');
    }
}
