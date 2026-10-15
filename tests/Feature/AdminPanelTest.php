<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Content;
use App\Models\Creator;
use App\Models\Genre;
use App\Models\Listener;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function admin(): User
    {
        return User::where('email', 'admin@iwire.app')->first();
    }

    public function test_guest_is_redirected_to_login_and_can_sign_in(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('Welcome back');
        $this->post('/login', ['email' => 'admin@iwire.app', 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => 'admin@iwire.app', 'password' => 'studio-ops-2026'])->assertRedirect('/');
    }

    public function test_every_screen_renders(): void
    {
        $this->actingAs($this->admin());
        $pending = Content::pending()->first();
        $live = Content::library()->where('type', 'Films')->first();
        $urls = [
            '/', '/queue', '/queue?view=table&tab=Music', "/review/{$pending->id}", "/review/{$pending->id}?reject=1",
            '/library', '/library?tab=Films&view=table&status=Live', "/library/{$live->id}",
            '/curation', '/curation?tab=Films', '/genres', '/genres?tab=moods',
            '/listeners', '/listeners?open='.Listener::first()->id, '/creators', '/creators/'.Creator::first()->id,
            '/reports', '/reports?status=All', '/comments', '/push', '/analytics', '/activity', '/settings', '/search?q=nova', '/search',
        ];
        foreach (['All', 'Music', 'Films'] as $ctx) {
            $this->post('/context', ['ctx' => $ctx]);
            foreach ($urls as $u) {
                $this->get($u)->assertOk();
            }
        }
        $this->get('/listeners/export')->assertOk();
    }

    public function test_review_actions(): void
    {
        $this->actingAs($this->admin());
        [$a, $b, $c] = Content::pending()->orderBy('submitted_at')->take(3)->get();

        $this->post("/review/{$a->id}/approve", ['title' => 'Renamed', 'genre' => $a->genre, 'explicit' => 0])->assertRedirect();
        $this->assertSame('Live', $a->fresh()->status);
        $this->assertSame('Renamed', $a->fresh()->title);

        $this->post("/review/{$b->id}/reject", ['reasons' => []])->assertSessionHasErrors('reasons');
        $this->post("/review/{$b->id}/reject", ['reasons' => ['Quality'], 'note' => 'Too quiet'])->assertRedirect();
        $this->assertSame('Rejected', $b->fresh()->status);

        $ids = Content::pending()->take(2)->pluck('id')->all();
        $this->post('/queue/bulk', ['ids' => $ids, 'action' => 'approve'])->assertRedirect();
        $this->assertSame(0, Content::whereIn('id', $ids)->where('status', 'Pending')->count());
    }

    public function test_library_curation_genre_and_people_actions(): void
    {
        $this->actingAs($this->admin());
        $t = Content::where('status', 'Live')->where('type', 'Music')->first();
        $this->post("/library/{$t->id}/publish")->assertRedirect();
        $this->assertSame('Unpublished', $t->fresh()->status);
        $this->put("/library/{$t->id}", ['title' => 'X', 'genre' => 'Lo-fi', 'explicit' => 1])->assertRedirect();
        $this->delete("/library/{$t->id}")->assertRedirect();
        $this->assertNull($t->fresh());

        $ids = Content::where('status', 'Live')->where('type', 'Music')->take(2)->pluck('id')->all();
        $this->post('/curation', ['tab' => 'Music', 'mode' => 'publish', 'payload' => json_encode(['rows' => [['name' => 'Fresh', 'ids' => $ids]]])])->assertRedirect();
        $this->assertDatabaseHas('curation_rows', ['home' => 'Music', 'name' => 'Fresh']);

        $this->post('/genres', ['kind' => 'music', 'name' => 'Jazz', 'color' => '#0A9BFF', 'art_seed' => 1, 'art_style' => 2])->assertRedirect();
        $g = Genre::where('name', 'Jazz')->first();
        $this->postJson('/genres/reorder', ['ids' => [$g->id]])->assertOk();
        $this->delete("/genres/{$g->id}")->assertRedirect();

        $l = Listener::first();
        $this->post("/listeners/{$l->id}/suspend");
        $this->assertSame('Suspended', $l->fresh()->status);

        $r = Report::where('status', 'Open')->whereNotNull('comment_id')->first();
        $this->post("/reports/{$r->id}/resolve");
        $this->assertSame('Hidden', Comment::find($r->comment_id)->status);

        $this->post('/push', ['title' => 'Hi', 'body' => 'There', 'audience' => 'All users', 'when' => 'now'])->assertRedirect();
        $this->put('/settings/platform', ['auto_flag_threshold' => 5, 'max_film_gb' => 8, 'max_track_minutes' => 15, 'require_explicit_label' => 1])->assertRedirect();
    }
}
