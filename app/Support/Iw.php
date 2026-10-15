<?php

namespace App\Support;

/**
 * Design-system helpers ported from the admin prototype (data.js):
 * generative cover art, status pill colours, compact numbers and SVG chart paths.
 */
class Iw
{
    private const PAL = [[205, 265], [330, 20], [160, 210], [28, 350], [275, 190], [45, 15], [190, 240], [300, 250], [120, 170], [10, 40], [220, 300], [350, 280]];

    public const ST = [
        'Pending' => ['bg' => 'rgba(245,176,65,.14)', 'fg' => '#F5C46B', 'dot' => '#F5B041'],
        'Live' => ['bg' => 'rgba(52,199,123,.14)', 'fg' => '#5FD99A', 'dot' => '#34C77B'],
        'Rejected' => ['bg' => 'rgba(240,82,82,.14)', 'fg' => '#F38A8A', 'dot' => '#F05252'],
        'Unpublished' => ['bg' => 'rgba(148,163,184,.14)', 'fg' => '#A7B4C6', 'dot' => '#8796AA'],
        'Flagged' => ['bg' => 'rgba(249,128,52,.15)', 'fg' => '#FBA26A', 'dot' => '#F98034'],
        'Open' => ['bg' => 'rgba(249,128,52,.15)', 'fg' => '#FBA26A', 'dot' => '#F98034'],
        'Resolved' => ['bg' => 'rgba(52,199,123,.14)', 'fg' => '#5FD99A', 'dot' => '#34C77B'],
        'Dismissed' => ['bg' => 'rgba(148,163,184,.14)', 'fg' => '#A7B4C6', 'dot' => '#8796AA'],
        'Active' => ['bg' => 'rgba(52,199,123,.14)', 'fg' => '#5FD99A', 'dot' => '#34C77B'],
        'Suspended' => ['bg' => 'rgba(240,82,82,.14)', 'fg' => '#F38A8A', 'dot' => '#F05252'],
        'Hidden' => ['bg' => 'rgba(148,163,184,.14)', 'fg' => '#A7B4C6', 'dot' => '#8796AA'],
        'Reported' => ['bg' => 'rgba(249,128,52,.15)', 'fg' => '#FBA26A', 'dot' => '#F98034'],
        'Visible' => ['bg' => 'rgba(52,199,123,.14)', 'fg' => '#5FD99A', 'dot' => '#34C77B'],
        'Sent' => ['bg' => 'rgba(10,155,255,.14)', 'fg' => '#6CC4FF', 'dot' => '#0A9BFF'],
        'Scheduled' => ['bg' => 'rgba(245,176,65,.14)', 'fg' => '#F5C46B', 'dot' => '#F5B041'],
    ];

    public static function art(int $i, ?int $style = null): string
    {
        [$a, $b] = self::PAL[$i % count(self::PAL)];
        $s = $style ?? ($i % 5);
        $base = "linear-gradient(145deg, hsl($a 65% 22%), hsl($b 70% 7%))";

        return match ($s) {
            0 => "radial-gradient(circle at 28% 30%, hsl($a 90% 62% / .95) 0, transparent 46%), radial-gradient(circle at 78% 78%, hsl($b 90% 55% / .85) 0, transparent 52%), $base",
            1 => "repeating-radial-gradient(circle at 70% 35%, hsl($a 85% 60% / .55) 0 2px, transparent 3px 14px), radial-gradient(circle at 70% 35%, hsl($b 90% 55%) 0, transparent 60%), $base",
            2 => "linear-gradient(180deg, transparent 55%, hsl($b 80% 8% / .9)), repeating-linear-gradient(115deg, hsl($a 80% 55% / .35) 0 10px, transparent 10px 26px), $base",
            3 => "conic-gradient(from 210deg at 35% 65%, hsl($a 90% 60%), hsl($b 85% 45%), hsl($a 70% 20%), hsl($a 90% 60%))",
            default => "radial-gradient(ellipse 60% 40% at 50% 100%, hsl($a 95% 60% / .9) 0, transparent 70%), radial-gradient(circle at 50% 38%, hsl($b 80% 70% / .9) 0 14%, transparent 15%), linear-gradient(180deg, hsl($b 60% 10%), hsl($a 70% 25%))",
        };
    }

    public static function st(string $status): array
    {
        return self::ST[$status] ?? self::ST['Unpublished'];
    }

    public static function k(int|float|null $n): string
    {
        $n = (float) $n;
        if ($n >= 1e6) {
            return number_format($n / 1e6, $n >= 1e7 ? 0 : 1).'M';
        }
        if ($n >= 1e3) {
            return number_format($n / 1e3, $n >= 1e4 ? 0 : 1).'K';
        }

        return (string) (int) $n;
    }

    public static function rnd(float $s): float
    {
        $x = sin($s * 9301 + 49297) * 233280;

        return $x - floor($x);
    }

    public static function wave(int $n, int $seed): array
    {
        $o = [];
        for ($i = 0; $i < $n; $i++) {
            $env = sin(M_PI * $i / $n) * .55 + .45;
            $o[] = (int) round((18 + self::rnd($seed + $i) * 82) * $env);
        }

        return $o;
    }

    public static function series(int $n, float $seed, float $base, float $amp, float $trend): array
    {
        $o = [];
        for ($i = 0; $i < $n; $i++) {
            $o[] = (int) round($base + $trend * $i + sin($i / 2.3 + $seed) * $amp + self::rnd($seed * 7 + $i) * $amp * .8);
        }

        return $o;
    }

    public static function path(array $arr, float $w, float $h, ?float $max = null, ?float $min = null): string
    {
        $max ??= max($arr);
        $min ??= 0;
        $st = $w / max(1, count($arr) - 1);
        $out = [];
        foreach (array_values($arr) as $i => $v) {
            $out[] = ($i ? 'L' : 'M').number_format($i * $st, 1, '.', '').' '.number_format($h - ($v - $min) / max(1e-9, $max - $min) * $h, 1, '.', '');
        }

        return implode(' ', $out);
    }

    public static function area(array $arr, float $w, float $h, ?float $max = null, ?float $min = null): string
    {
        return self::path($arr, $w, $h, $max, $min)." L$w $h L0 $h Z";
    }

    public static function initials(string $name): string
    {
        return mb_substr(collect(preg_split('/\s+/', trim($name)))->map(fn ($p) => mb_substr($p, 0, 1))->implode(''), 0, 2);
    }

    public static function waiting(int $hrs): string
    {
        return $hrs >= 48 ? 'Waiting '.intdiv($hrs, 24).'d '.($hrs % 24).'h' : "Waiting $hrs hrs";
    }

    public static function waitShort(int $hrs): string
    {
        return $hrs >= 48 ? intdiv($hrs, 24).'d '.($hrs % 24).'h' : $hrs.'h';
    }

    /** Parse "3:24" or "1h 04m" / "58m" into seconds. */
    public static function seconds(string $dur): int
    {
        if (preg_match('/^(\d+):(\d+)$/', $dur, $m)) {
            return (int) $m[1] * 60 + (int) $m[2];
        }
        $h = preg_match('/(\d+)h/', $dur, $m) ? (int) $m[1] : 0;
        $mm = preg_match('/(\d+)m/', $dur, $m) ? (int) $m[1] : 0;

        return $h * 3600 + $mm * 60;
    }
}
