<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Content;
use App\Models\Creator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Search, comments, profile, creator uploads and legal pages. */
class MobileApiV1ExtrasTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function token(string $email = 'listener@iwire.app', string $password = 'listener123'): string
    {
        return $this->postJson('/api/v1/auth/login', compact('email', 'password'))->json('data.token');
    }

    /** Drop the control-room session so the next API call authenticates by bearer token only, like a phone. */
    private function asAppUser(): void
    {
        $this->app['auth']->forgetGuards();
        $this->app['auth']->guard('web')->logout();
    }

    public function test_search(): void
    {
        $t = $this->token();
        $this->withToken($t)->getJson('/api/v1/search?q=midnight')->assertOk()
            ->assertJsonPath('data.tracks.0.title', 'Midnight Transit')
            ->assertJsonStructure(['data' => ['query', 'tracks' => [['id', 'title', 'artist', 'cover', 'genre', 'duration']], 'films', 'creators']]);
        $this->withToken($t)->getJson('/api/v1/search?q=ember&type=films')->assertOk()
            ->assertJsonPath('data.films.0.title', 'Ember of the North')->assertJsonMissingPath('data.tracks');
        $c = $this->withToken($t)->getJson('/api/v1/search?q=nova&type=creators')->assertOk()
            ->assertJsonStructure(['data' => ['creators' => [['id', 'name', 'type', 'avatar', 'uploads']]]])->json('data.creators.0');
        $this->withToken($t)->getJson("/api/v1/creators/{$c['id']}")->assertOk()
            ->assertJsonStructure(['data' => ['id', 'name', 'type', 'avatar', 'uploads', 'bio', 'total_plays', 'tracks', 'films']]);
        // Pending content never appears in search.
        $this->withToken($t)->getJson('/api/v1/search?q=neon')->assertOk()->assertJsonCount(0, 'data.tracks');
        $this->withToken($t)->getJson('/api/v1/search?q=a')->assertStatus(422);
    }

    public function test_comments_round_trip_with_moderation(): void
    {
        $t = $this->token();
        $track = Content::where('type', 'Music')->where('status', 'Live')->first();
        $before = $track->comments_count;

        $posted = $this->withToken($t)->postJson("/api/v1/music/tracks/{$track->id}/comments", ['body' => 'Love this one'])
            ->assertCreated()->assertJsonPath('data.body', 'Love this one')->assertJsonPath('data.is_mine', true)
            ->assertJsonPath('data.user.name', 'Demo Listener')->json('data');
        $this->assertSame($before + 1, $track->fresh()->comments_count);

        $list = $this->withToken($t)->getJson("/api/v1/music/tracks/{$track->id}/comments")->assertOk()
            ->assertJsonStructure(['data' => [['id', 'body', 'created_at', 'time_ago', 'is_mine', 'user' => ['id', 'name', 'avatar']]], 'meta'])->json('data');
        $this->assertSame($posted['id'], $list[0]['id']);

        // Admin hides it in Moderation → gone from the app.
        $this->actingAs(User::where('account_type', 'admin')->first())->post("/comments/{$posted['id']}/visibility");
        $this->asAppUser();
        $ids = collect($this->withToken($t)->getJson("/api/v1/music/tracks/{$track->id}/comments")->json('data'))->pluck('id');
        $this->assertNotContains($posted['id'], $ids);

        // Users can only delete their own comments.
        $other = Comment::where('id', '!=', $posted['id'])->first();
        $this->withToken($t)->deleteJson("/api/v1/comments/{$other->id}")->assertNotFound();
        $this->withToken($t)->deleteJson("/api/v1/comments/{$posted['id']}")->assertOk();

        $film = Content::where('type', 'Films')->where('status', 'Live')->first();
        $this->withToken($t)->postJson("/api/v1/films/{$film->id}/comments", ['body' => 'Great film'])->assertCreated();
        $this->withToken($t)->postJson("/api/v1/films/{$film->id}/comments", ['body' => ''])->assertStatus(422);
        $this->withToken($t)->postJson("/api/v1/music/tracks/{$film->id}/comments", ['body' => 'x'])->assertNotFound();
    }

    public function test_profile(): void
    {
        Storage::fake('public');
        $t = $this->token();
        $this->withToken($t)->getJson('/api/v1/profile')->assertOk()
            ->assertJsonStructure(['data' => ['user' => ['id', 'name', 'email', 'account_type', 'avatar', 'gender', 'date_of_birth', 'country', 'bio', 'created_at']]]);

        $this->withToken($t)->putJson('/api/v1/profile', ['name' => 'Ayesha K', 'gender' => 'female', 'date_of_birth' => '1998-04-12', 'country' => 'Pakistan', 'bio' => 'Hi'])
            ->assertOk()->assertJsonPath('data.user.name', 'Ayesha K')->assertJsonPath('data.user.gender', 'female')
            ->assertJsonPath('data.user.date_of_birth', '1998-04-12');
        $this->assertDatabaseHas('listeners', ['email' => 'listener@iwire.app', 'name' => 'Ayesha K']);
        $this->withToken($t)->putJson('/api/v1/profile', ['gender' => 'robot'])->assertStatus(422);
        $this->withToken($t)->putJson('/api/v1/profile', ['email' => 'creator@iwire.app'])->assertStatus(422);

        $url = $this->withToken($t)->post('/api/v1/profile/avatar', ['avatar' => UploadedFile::fake()->image('me.jpg', 300, 300)], ['Accept' => 'application/json'])
            ->assertOk()->json('data.user.avatar');
        $this->assertStringContainsString('/storage/avatars/', $url);
        $this->withToken($t)->deleteJson('/api/v1/profile/avatar')->assertOk()->assertJsonPath('data.user.avatar', null);

        $this->withToken($t)->putJson('/api/v1/profile/password', ['current_password' => 'wrong', 'password' => 'newpass123', 'password_confirmation' => 'newpass123'])->assertStatus(422);
        $this->withToken($t)->putJson('/api/v1/profile/password', ['current_password' => 'listener123', 'password' => 'newpass123', 'password_confirmation' => 'newpass123'])->assertOk();
        $this->postJson('/api/v1/auth/login', ['email' => 'listener@iwire.app', 'password' => 'newpass123'])->assertOk();
    }

    public function test_delete_account(): void
    {
        $t = $this->token();
        $this->withToken($t)->deleteJson('/api/v1/profile', ['password' => 'nope'])->assertStatus(422);
        $this->withToken($t)->deleteJson('/api/v1/profile', ['password' => 'listener123'])->assertOk();
        $this->assertDatabaseMissing('users', ['email' => 'listener@iwire.app']);
        $this->assertDatabaseMissing('listeners', ['email' => 'listener@iwire.app']);
    }

    public function test_creator_upload_flow_reaches_review_queue(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $t = $this->token('creator@iwire.app', 'creator123');

        $up = $this->withToken($t)->post('/api/v1/creator/uploads', [
            'type' => 'music', 'title' => 'First Light', 'genre' => 'Electronic', 'mood' => 'Chill', 'description' => 'Debut',
            'explicit' => 0, 'duration' => 204,
            'file' => UploadedFile::fake()->create('song.mp3', 300, 'audio/mpeg'),
            'cover' => UploadedFile::fake()->image('cover.jpg', 600, 600),
        ], ['Accept' => 'application/json'])->assertCreated()
            ->assertJsonPath('data.status', 'pending')->assertJsonPath('data.duration', 204)->json('data');

        $c = Content::find($up['id']);
        $this->assertSame('Pending', $c->status);
        $this->assertSame('3:24', $c->duration);

        // Shows in the admin Review Queue; admin rejects with a reason.
        $admin = User::where('account_type', 'admin')->first();
        $this->actingAs($admin)->get('/queue')->assertSee('First Light');
        $this->actingAs($admin)->post("/review/{$c->id}/reject", ['reasons' => ['Quality'], 'note' => 'Too quiet']);
        $this->asAppUser();

        $this->withToken($t)->getJson("/api/v1/creator/uploads/{$c->id}")->assertOk()
            ->assertJsonPath('data.status', 'rejected')->assertJsonPath('data.reject_reasons', ['Quality'])->assertJsonPath('data.reject_note', 'Too quiet');
        $this->withToken($t)->getJson('/api/v1/creator/uploads?status=rejected')->assertOk()->assertJsonPath('meta.total', 1);
        $this->withToken($t)->getJson('/api/v1/creator/stats')->assertOk()->assertJsonPath('data.rejected', 1);
        $this->withToken($t)->deleteJson("/api/v1/creator/uploads/{$c->id}")->assertStatus(409);

        // A pending film can be withdrawn.
        $film = $this->withToken($t)->post('/api/v1/creator/uploads', [
            'type' => 'films', 'title' => 'Short One', 'genre' => 'Drama', 'duration' => 3900,
            'file' => UploadedFile::fake()->create('film.mp4', 500, 'video/mp4'),
            'cover' => UploadedFile::fake()->image('poster.jpg', 400, 600),
        ], ['Accept' => 'application/json'])->assertCreated()->json('data');
        $this->assertSame('1h 05m', Content::find($film['id'])->duration);
        $this->withToken($t)->deleteJson("/api/v1/creator/uploads/{$film['id']}")->assertOk();

        // Wrong genre for the type is rejected; listeners can't use creator endpoints.
        $this->withToken($t)->post('/api/v1/creator/uploads', ['type' => 'music', 'title' => 'X', 'genre' => 'Drama', 'duration' => 10], ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonValidationErrors('genre');
        $this->app['auth']->forgetGuards();
        $this->withToken($this->token())->getJson('/api/v1/creator/uploads')->assertForbidden()->assertJsonPath('message', 'This feature is for creator accounts.');
    }

    public function test_share_links(): void
    {
        $t = $this->token();
        $track = Content::where('type', 'Music')->where('status', 'Live')->first();
        $film = Content::where('type', 'Films')->where('status', 'Live')->first();

        $url = $this->withToken($t)->getJson("/api/v1/music/tracks/{$track->id}")->assertOk()->json('data.share_url');
        $this->assertStringEndsWith("/s/track/{$track->id}", $url);
        $this->withToken($t)->getJson("/api/v1/films/{$film->id}")->assertOk()->assertJsonPath('data.share_url', url("/s/film/{$film->id}"));

        $shares = $track->shares;
        $this->withToken($t)->postJson("/api/v1/music/tracks/{$track->id}/share")->assertOk()
            ->assertJsonPath('data.share_url', $url)->assertJsonStructure(['data' => ['share_url', 'text']]);
        $this->assertSame($shares + 1, $track->fresh()->shares);
        $this->withToken($t)->postJson("/api/v1/films/{$film->id}/share")->assertOk();

        // Public landing page with preview tags and a deep link; unpublished items 404.
        $this->get("/s/track/{$track->id}")->assertOk()->assertSee('og:title', false)->assertSee("iwire://track/{$track->id}", false);
        $this->get("/s/film/{$film->id}")->assertOk()->assertSee("iwire://film/{$film->id}", false);
        $pending = Content::pending()->where('type', 'Music')->first();
        $this->get("/s/track/{$pending->id}")->assertNotFound();
        $this->get("/s/film/{$track->id}")->assertNotFound();
    }

    public function test_music_genres_and_moods(): void
    {
        $t = $this->token();
        $this->withToken($t)->getJson('/api/v1/music/genres')->assertOk()->assertJsonPath('data.0.name', 'Hip-Hop');
        $this->withToken($t)->getJson('/api/v1/music/moods')->assertOk()->assertJsonPath('data.0.name', 'Chill');
    }

    public function test_terms_and_privacy_pages(): void
    {
        $this->getJson('/api/v1/pages/terms')->assertOk()->assertJsonPath('data.title', 'Terms & Conditions')->assertJsonPath('data.content', '');
        $this->actingAs(User::where('account_type', 'admin')->first())
            ->put('/settings/pages', ['terms' => "Be kind.\n\nNo piracy.", 'privacy' => 'We keep your data safe.'])->assertRedirect();
        $this->getJson('/api/v1/pages/terms')->assertOk()->assertJsonPath('data.content', "Be kind.\n\nNo piracy.")
            ->assertJsonStructure(['data' => ['slug', 'title', 'content', 'updated_at']]);
        $this->getJson('/api/v1/pages/privacy')->assertOk()->assertJsonPath('data.title', 'Privacy Policy');
        $this->getJson('/api/v1/pages/cookies')->assertNotFound();
    }
}
