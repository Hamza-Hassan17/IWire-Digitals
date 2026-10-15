<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Comment;
use App\Models\Content;
use App\Models\Creator;
use App\Models\CurationRow;
use App\Models\Genre;
use App\Models\HeroSlide;
use App\Models\Listener;
use App\Models\PushNotification;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds the admin panel with the dataset from the design prototype (_design_extracted/data.js).
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(['email' => 'admin@iwire.app'], [
            'name' => 'Platform Admin',
            'password' => Hash::make('studio-ops-2026'),
            'account_type' => 'admin',
        ]);

        // Creators ----------------------------------------------------------
        $people = [
            ['Nova Reyes', 'Musician', 3.2e6, 'Mar 2025'], ['Kairo Banks', 'Musician', 2.8e6, 'Jan 2025'],
            ['Lumen Hale', 'Musician', 1.9e6, 'Apr 2025'], ['Amara Sol', 'Musician', 1.4e6, 'Feb 2025'],
            ['The Quiet Fleet', 'Musician', 8.1e5, 'Jun 2025'], ['Dre Okafor', 'Musician', 1.1e6, 'May 2025'],
            ['Ilse Varga', 'Filmmaker', 1.6e6, 'Jan 2025'], ['Tomas Ruiz', 'Filmmaker', 9.4e5, 'Jul 2025'],
            ['Priya Desai', 'Filmmaker', 8.8e5, 'Mar 2025'], ['Odile Fontaine', 'Filmmaker', 7.1e5, 'Aug 2025'],
            ['Kwame Asante', 'Filmmaker', 6.2e5, 'May 2025'], ['Leo Marchetti', 'Filmmaker', 5.3e5, 'Apr 2025'],
            ['Mika Tanaka', 'Musician', 4.4e5, 'Jun 2025'], ['Juno Vance', 'Musician', 4.0e5, 'Jul 2025'],
            ['Ezra Lune', 'Musician', 3.6e5, 'Feb 2025'], ['Saint Marlo', 'Musician', 3.0e5, 'Aug 2025'],
            ['Sora Kim', 'Filmmaker', 2.0e5, 'Jun 2025'], ['Nadia Bloom', 'Filmmaker', 2.5e5, 'May 2025'],
        ];
        $creators = [];
        foreach ($people as $i => [$name, $type, $plays, $joined]) {
            $creators[$name] = Creator::create([
                'name' => $name,
                'email' => preg_replace('/[^a-z]/', '.', strtolower($name)).'@mail.com',
                'type' => $type,
                'status' => $name === 'Leo Marchetti' ? 'Suspended' : 'Active',
                'total_plays' => (int) $plays,
                'art_seed' => $i + 1,
                'joined_at' => Carbon::parse('1 '.$joined),
            ]);
        }

        // Live library: tracks ---------------------------------------------
        $tracks = [
            ['Midnight Transit', 'Nova Reyes', 'Electronic', 'Late Night', '3:42', 1284000, 'Live', 'Sep 2'],
            ['Gold Teeth Sermon', 'Kairo Banks', 'Hip-Hop', 'Hype', '2:58', 982400, 'Live', 'Aug 28'],
            ['Soft Static', 'Lumen Hale', 'Lo-fi', 'Focus', '2:31', 871200, 'Live', 'Aug 21'],
            ['Velvet Hours', 'Amara Sol', 'R&B', 'Late Night', '4:05', 764900, 'Live', 'Aug 19'],
            ['Paper Planets', 'The Quiet Fleet', 'Electronic', 'Chill', '3:19', 612300, 'Live', 'Aug 11'],
            ['Concrete Bloom', 'Dre Okafor', 'Hip-Hop', 'Hype', '3:07', 540800, 'Live', 'Aug 9'],
            ['Rainroom', 'Mika Tanaka', 'Lo-fi', 'Chill', '2:44', 438100, 'Live', 'Jul 30'],
            ['Afterglow Drive', 'Juno Vance', 'R&B', 'Late Night', '3:51', 402700, 'Unpublished', 'Jul 24'],
            ['Pulse Theory', 'Ezra Lune', 'Electronic', 'Focus', '5:12', 356000, 'Live', 'Jul 18'],
            ['Blue Hour Tape', 'Saint Marlo', 'Lo-fi', 'Focus', '2:16', 298400, 'Flagged', 'Jul 12'],
            ['Crown Season', 'Kairo Banks', 'Hip-Hop', 'Hype', '3:33', 264900, 'Live', 'Jul 3'],
            ['Silk Signal', 'Amara Sol', 'R&B', 'Chill', '3:28', 201300, 'Rejected', 'Jun 29'],
        ];
        $t = [];
        foreach ($tracks as $i => $r) {
            $t[$i] = Content::create([
                'creator_id' => $creators[$r[1]]->id, 'type' => 'Music', 'title' => $r[0], 'genre' => $r[2], 'mood' => $r[3],
                'description' => "{$r[3]} ".strtolower($r[2])." from {$r[1]}.",
                'duration' => $r[4], 'plays' => $r[5], 'shares' => (int) round($r[5] / 140), 'comments_count' => (int) round($r[5] / 900),
                'status' => $r[6], 'art_seed' => $i, 'art_style' => $i % 5, 'explicit' => $i % 4 === 1,
                'submitted_at' => Carbon::parse($r[7].' 2026'), 'reviewed_at' => Carbon::parse($r[7].' 2026')->addDay(),
            ]);
        }

        // Live library: films ----------------------------------------------
        $films = [
            ['Ember of the North', 'Ilse Varga', 'Fantasy', '1h 52m', 842000, 'Live', 62, 'Sep 4'],
            ['The Last Lighthouse', 'Tomas Ruiz', 'Drama', '1h 38m', 731500, 'Live', 34, 'Aug 30'],
            ['Signal Lost', 'Priya Desai', 'Action', '2h 04m', 688200, 'Live', 81, 'Aug 25'],
            ['Salt & Static', 'Odile Fontaine', 'Documentary', '1h 12m', 512300, 'Live', 15, 'Aug 20'],
            ['Glass Harbor', 'Kwame Asante', 'Drama', '1h 46m', 466900, 'Live', 48, 'Aug 14'],
            ['Hollow Crown', 'Ilse Varga', 'Fantasy', '2h 11m', 401400, 'Live', 7, 'Aug 7'],
            ['Night Runners', 'Leo Marchetti', 'Action', '1h 41m', 388000, 'Unpublished', 55, 'Jul 29'],
            ['Roots Underground', 'Nadia Bloom', 'Documentary', '58m', 254700, 'Live', 90, 'Jul 22'],
            ['Paper Moons', 'Sora Kim', 'Drama', '1h 29m', 198600, 'Flagged', 22, 'Jul 15'],
            ['Ironvale', 'Leo Marchetti', 'Action', '1h 55m', 142000, 'Live', 40, 'Jul 8'],
        ];
        $f = [];
        foreach ($films as $i => $r) {
            $f[$i] = Content::create([
                'creator_id' => $creators[$r[1]]->id, 'type' => 'Films', 'title' => $r[0], 'genre' => $r[2],
                'description' => 'A '.strtolower($r[2])." feature directed by {$r[1]}.",
                'duration' => $r[3], 'plays' => $r[4], 'shares' => (int) round($r[4] / 120), 'comments_count' => (int) round($r[4] / 700),
                'status' => $r[5], 'progress' => $r[6], 'art_seed' => $i + 3, 'art_style' => [2, 4, 0, 1, 3][$i % 5],
                'submitted_at' => Carbon::parse($r[7].' 2026'), 'reviewed_at' => Carbon::parse($r[7].' 2026')->addDay(),
            ]);
        }

        // Review queue (pending submissions) --------------------------------
        $queue = [
            ['Neon Psalms', 'Kairo Banks', 'Music', 'Hip-Hop', '3:24', 52], ['Tidewater', 'Odile Fontaine', 'Films', 'Documentary', '1h 04m', 46],
            ['Low Orbit', 'Ezra Lune', 'Music', 'Electronic', '4:48', 40], ['Satin Rooftop', 'Amara Sol', 'Music', 'R&B', '3:11', 34],
            ['The Cartographer', 'Sora Kim', 'Films', 'Fantasy', '1h 47m', 30], ['Dust Library', 'Mika Tanaka', 'Music', 'Lo-fi', '2:39', 27],
            ['Afterparty Physics', 'Nova Reyes', 'Music', 'Electronic', '3:57', 23], ['Ricochet', 'Leo Marchetti', 'Films', 'Action', '1h 36m', 20],
            ['Sunday Dialect', 'Dre Okafor', 'Music', 'Hip-Hop', '3:02', 17], ['Glassworks', 'Kwame Asante', 'Films', 'Drama', '1h 22m', 14],
            ['Cloud Library', 'Lumen Hale', 'Music', 'Lo-fi', '2:12', 9], ['Still Water Run', 'Juno Vance', 'Music', 'R&B', '3:44', 4],
        ];
        $moodFor = ['Hip-Hop' => 'Hype', 'Electronic' => 'Late Night', 'R&B' => 'Late Night', 'Lo-fi' => 'Focus'];
        foreach ($queue as $i => $r) {
            $music = $r[2] === 'Music';
            Content::create([
                'creator_id' => $creators[$r[1]]->id, 'type' => $r[2], 'title' => $r[0], 'genre' => $r[3],
                'mood' => $music ? ($moodFor[$r[3]] ?? 'Chill') : null,
                'description' => $music
                    ? 'A '.strtolower($r[3])." cut recorded at Blue Room Studios. Produced by {$r[1]} with live drums and analog synths."
                    : 'A '.strtolower($r[3])." feature following a small crew across one long winter. Directed by {$r[1]}.",
                'duration' => $r[4], 'status' => 'Pending', 'explicit' => $i % 3 === 0,
                'art_seed' => $i + 5, 'art_style' => $music ? $i % 5 : 2 + ($i % 3),
                'submitted_at' => now()->subHours($r[5])->subMinutes(($i * 17) % 60),
            ]);
        }

        // Listeners ----------------------------------------------------------
        $ln = ['Ayesha Khan', 'Marcus Bell', 'Lena Fischer', 'Jordan Pike', 'Chen Wei', 'Sofia Marín', 'Tariq Hale', 'Grace Obi', 'Noah Brandt', 'Isla Moreau', 'Ravi Menon', 'Zara Lindqvist', 'Owen Carter', 'Mei Ishikawa'];
        $joined = ['Oct 2', 'Sep 28', 'Sep 14', 'Sep 3', 'Aug 22', 'Aug 9', 'Jul 30', 'Jul 12', 'Jun 29', 'Jun 3', 'May 18', 'Apr 30', 'Apr 2', 'Mar 21'];
        $lastMin = [2, 14, 60, 180, 300, 1440, 1500, 2880, 5760, 10080, 20160, 30240, 43200, 86400];
        $streams = [4210, 3876, 3102, 2840, 2611, 2207, 1980, 1644, 1310, 980, 742, 512, 301, 96];
        $places = ['Lagos, NG', 'London, UK', 'Berlin, DE', 'Austin, US', 'Shanghai, CN', 'Madrid, ES', 'Dubai, AE', 'Accra, GH', 'Hamburg, DE', 'Paris, FR', 'Mumbai, IN', 'Stockholm, SE', 'Toronto, CA', 'Tokyo, JP'];
        $L = [];
        foreach ($ln as $i => $name) {
            [$first, $last] = explode(' ', strtolower($name));
            $L[$name] = Listener::create([
                'name' => $name, 'email' => $first.'.'.mb_substr($last, 0, 1).'@mail.com',
                'status' => in_array($i, [9, 13]) ? 'Suspended' : 'Active', 'streams' => $streams[$i],
                'device' => $i % 3 === 2 ? 'iOS' : 'Android', 'location' => $places[$i], 'art_seed' => $i + 2,
                'last_active_at' => now()->subMinutes($lastMin[$i]), 'joined_at' => Carbon::parse($joined[$i].' 2026'),
            ]);
        }

        // Comments -----------------------------------------------------------
        $all = array_merge($t, $f); // indices 0-11 tracks, 12-21 films
        $comments = [
            ['Ayesha Khan', 'This bridge at 2:10 is unreal. On repeat all week.', 0, 'Visible', 2],
            ['Marcus Bell', 'Check out my channel for free beats 🔥 link in bio', 1, 'Reported', 18],
            ['Lena Fischer', 'The cinematography in the lighthouse scene… chef’s kiss.', 13, 'Visible', 42],
            ['Jordan Pike', 'Mid. The first one was better honestly.', 14, 'Visible', 60],
            ['Tariq Hale', '[removed slur] this artist is trash', 5, 'Hidden', 120],
            ['Grace Obi', 'Perfect focus music for exams, thank you Lumen!', 2, 'Visible', 180],
            ['Noah Brandt', 'Spoiler: the captain dies at the end lol', 12, 'Reported', 300],
            ['Chen Wei', 'Need the instrumental version of this asap', 3, 'Visible', 360],
            ['Sofia Marín', 'Documentary made me cry. Important story.', 15, 'Visible', 1440],
            ['Owen Carter', 'Stolen sample, this is from an old soul record', 9, 'Reported', 1500],
        ];
        $C = [];
        foreach ($comments as $i => $r) {
            $C[$i] = Comment::create([
                'listener_id' => $L[$r[0]]->id, 'content_id' => $all[$r[2]]->id, 'body' => $r[1], 'status' => $r[3],
                'created_at' => now()->subMinutes($r[4]), 'updated_at' => now()->subMinutes($r[4]),
            ]);
        }

        // Reports ------------------------------------------------------------
        $reports = [
            ['Blue Hour Tape', 'Saint Marlo', 'Track', 'Copyright infringement', 14, 'Oct 14', 'Open', $t[9], null, 0],
            ['Paper Moons', 'Sora Kim', 'Film', 'Inappropriate content', 9, 'Oct 13', 'Open', $f[8], null, 0],
            ['“Check out my channel…”', 'Marcus Bell', 'Comment', 'Spam', 6, 'Oct 13', 'Open', null, $C[1], 1],
            ['“Spoiler: the captain…”', 'Noah Brandt', 'Comment', 'Spoilers', 4, 'Oct 12', 'Open', null, $C[6], 7],
            ['Night Runners', 'Leo Marchetti', 'Film', 'Misleading metadata', 3, 'Oct 11', 'Resolved', $f[6], null, 0],
            ['Afterglow Drive', 'Juno Vance', 'Track', 'Explicit not labelled', 5, 'Oct 9', 'Resolved', $t[7], null, 0],
            ['“[removed]”', 'Tariq Hale', 'Comment', 'Hate speech', 11, 'Oct 8', 'Resolved', null, $C[4], 5],
            ['Rainroom', 'Mika Tanaka', 'Track', 'Low audio quality', 2, 'Oct 6', 'Dismissed', $t[6], null, 0],
        ];
        foreach ($reports as $r) {
            $d = Carbon::parse($r[5].' 2026 12:00');
            Report::create([
                'label' => $r[0], 'subject_name' => $r[1], 'kind' => $r[2], 'reason' => $r[3], 'count' => $r[4], 'status' => $r[6],
                'content_id' => $r[7]?->id, 'comment_id' => $r[8]?->id, 'art_seed' => $r[9], 'created_at' => $d, 'updated_at' => $d,
            ]);
        }

        // Activity log -------------------------------------------------------
        $activity = [
            ['check_circle', '#5FD99A', 'Approved', '“Pulse Theory” by Ezra Lune', 0, '10:42', 'Music'],
            ['cancel', '#F38A8A', 'Rejected', '“Silk Signal” by Amara Sol — Metadata issues', 0, '10:31', 'Music'],
            ['dashboard_customize', '#6CC4FF', 'Curation published', 'Films home · Hero carousel (5 slides)', 0, '09:58', 'Films'],
            ['campaign', '#6CC4FF', 'Notification sent', '“New drop from Nova Reyes” → Music users', 0, '09:15', 'Music'],
            ['visibility_off', '#A7B4C6', 'Unpublished', '“Night Runners” by Leo Marchetti', 1, '18:20', 'Films'],
            ['block', '#F38A8A', 'Suspended user', 'Isla Moreau (listener)', 1, '16:02', 'All'],
            ['check_circle', '#5FD99A', 'Approved', '“Roots Underground” by Nadia Bloom', 1, '14:47', 'Films'],
            ['flag', '#FBA26A', 'Report resolved', '“Afterglow Drive” — marked explicit', 1, '11:30', 'Music'],
            ['check_circle', '#5FD99A', 'Approved', '“Concrete Bloom” by Dre Okafor', 2, '17:12', 'Music'],
            ['dashboard_customize', '#6CC4FF', 'Curation published', 'Music home · Find Your Mood', 2, '15:40', 'Music'],
            ['cancel', '#F38A8A', 'Rejected', '“Sideways” by Juno Vance — Quality', 2, '13:08', 'Music'],
            ['block', '#F38A8A', 'Suspended user', 'Leo Marchetti (creator)', 3, '10:22', 'Films'],
        ];
        foreach (array_reverse($activity) as $r) {
            $d = now()->subDays($r[4])->setTimeFromTimeString($r[5]);
            if ($d->isFuture()) {
                $d = now()->subMinutes(5);
            }
            Activity::create(['icon' => $r[0], 'color' => $r[1], 'action' => $r[2], 'target' => $r[3], 'context' => $r[6], 'created_at' => $d, 'updated_at' => $d]);
        }

        // Push notifications -------------------------------------------------
        $sent = [
            ['New drop from Nova Reyes', '“Midnight Transit” is out now. Tap to listen.', 'Music users', now()->setTime(9, 15), 184000, 12.4, 'Sent'],
            ['Weekend premiere', 'Ember of the North streams free all weekend.', 'Films users', Carbon::parse('Oct 18 2026 18:00'), null, null, 'Scheduled'],
            ['Your Late Night mix is ready', 'Fresh R&B and electronic for after dark.', 'Music users', Carbon::parse('Oct 11 2026 21:00'), 172000, 9.8, 'Sent'],
            ['Creators: new upload limits', 'You can now upload films up to 8 GB.', 'Creators', Carbon::parse('Oct 8 2026 12:00'), 1200, 41.0, 'Sent'],
            ['Trending this week', 'Signal Lost is #1 on iWire Films.', 'All users', Carbon::parse('Oct 5 2026 17:30'), 326000, 8.1, 'Sent'],
        ];
        foreach ($sent as $r) {
            PushNotification::create(['title' => $r[0], 'body' => $r[1], 'audience' => $r[2], 'send_at' => $r[3], 'reach' => $r[4], 'ctr' => $r[5], 'status' => $r[6]]);
        }

        // Genres & moods -----------------------------------------------------
        $genres = [
            'music' => [['Hip-Hop', '#0A9BFF'], ['R&B', '#7A6CFF'], ['Electronic', '#18C6D8'], ['Lo-fi', '#4F86C6'], ['Afrobeats', '#2CB58A'], ['Soul', '#B783FF']],
            'films' => [['Drama', '#4F86C6'], ['Action', '#0A9BFF'], ['Documentary', '#2CB58A'], ['Fantasy', '#7A6CFF'], ['Thriller', '#18C6D8']],
            'moods' => [['Chill', '#18C6D8'], ['Hype', '#0A9BFF'], ['Focus', '#4F86C6'], ['Late Night', '#7A6CFF']],
        ];
        foreach ($genres as $kind => $list) {
            foreach ($list as $i => [$name, $color]) {
                Genre::create(['kind' => $kind, 'name' => $name, 'color' => $color, 'position' => $i, 'art_seed' => $i + ($kind === 'films' ? 4 : ($kind === 'moods' ? 8 : 0)), 'art_style' => $i % 5]);
            }
        }

        // Home curation ------------------------------------------------------
        $ids = fn (array $list, array $idx) => array_map(fn ($i) => $list[$i]->id, $idx);
        CurationRow::create(['home' => 'Music', 'name' => 'Find Your Mood', 'position' => 0, 'content_ids' => $ids($t, [0, 3, 4, 6, 2])]);
        CurationRow::create(['home' => 'Music', 'name' => 'Top Picks', 'position' => 1, 'content_ids' => $ids($t, [1, 5, 8, 10, 9])]);
        CurationRow::create(['home' => 'Films', 'name' => 'Trending Now', 'position' => 0, 'content_ids' => $ids($f, [2, 0, 1, 4, 3])]);
        CurationRow::create(['home' => 'Films', 'name' => 'New', 'position' => 1, 'content_ids' => $ids($f, [5, 7, 9, 6])]);
        foreach ([[0, 'The last fire-keeper heads north · Epic Fantasy'], [2, 'One signal. Twelve hours. · Action'], [1, 'A keeper, a storm, a secret · Drama'], [3, 'Life on the salt flats · Documentary']] as $p => [$fi, $tag]) {
            HeroSlide::create(['content_id' => $f[$fi]->id, 'tagline' => $tag, 'position' => $p]);
        }

        $this->call(DemoAppUsersSeeder::class);
    }
}
