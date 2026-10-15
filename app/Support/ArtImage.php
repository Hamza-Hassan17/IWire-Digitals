<?php

namespace App\Support;

/**
 * Renders the generative cover art (see Iw::art) as a PNG so mobile clients get a real image URL
 * for items that don't have an uploaded cover yet. Output is cached on disk by the caller.
 */
class ArtImage
{
    private const PAL = [[205, 265], [330, 20], [160, 210], [28, 350], [275, 190], [45, 15], [190, 240], [300, 250], [120, 170], [10, 40], [220, 300], [350, 280]];

    public static function png(int $W, int $H, int $seed, int $style): string
    {
        $w = max(1, intdiv($W, 3));
        $h = max(1, intdiv($H, 3));
        [$a, $b] = self::PAL[$seed % count(self::PAL)];
        $base1 = self::hsl($a, .65, .22);
        $base2 = self::hsl($b, .70, .07);
        $glowA = self::hsl($a, .90, .62);
        $glowB = self::hsl($b, .90, .55);
        // Glow positions vary by style so different items look distinct.
        $spots = [
            [[.28, .30, .46], [.78, .78, .52]],
            [[.70, .35, .60], [.20, .80, .35]],
            [[.50, .20, .45], [.50, .95, .55]],
            [[.35, .65, .55], [.85, .20, .40]],
            [[.50, .38, .30], [.50, 1.0, .60]],
        ][$style % 5];

        $img = imagecreatetruecolor($w, $h);
        $diag = max(1, $w + $h);
        $min = min($w, $h);
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $t = ($x + $y) / $diag;
                $c = self::mix($base1, $base2, $t);
                foreach ([[$spots[0], $glowA], [$spots[1], $glowB]] as [[$sx, $sy, $r], $col]) {
                    $d = hypot($x - $sx * $w, $y - $sy * $h) / ($r * $min);
                    if ($d < 1) {
                        $c = self::mix($c, $col, (1 - $d) ** 1.6 * .9);
                    }
                }
                imagesetpixel($img, $x, $y, ($c[0] << 16) | ($c[1] << 8) | $c[2]);
            }
        }
        // Rendered at a third of the size; smooth gradients upscale without visible loss.
        $out = imagecreatetruecolor($W, $H);
        imagecopyresampled($out, $img, 0, 0, 0, 0, $W, $H, $w, $h);
        ob_start();
        imagepng($out, null, 6);

        return ob_get_clean();
    }

    private static function mix(array $p, array $q, float $t): array
    {
        return [(int) ($p[0] + ($q[0] - $p[0]) * $t), (int) ($p[1] + ($q[1] - $p[1]) * $t), (int) ($p[2] + ($q[2] - $p[2]) * $t)];
    }

    private static function hsl(float $h, float $s, float $l): array
    {
        $h = fmod($h, 360) / 360;
        $q = $l < .5 ? $l * (1 + $s) : $l + $s - $l * $s;
        $p = 2 * $l - $q;
        $f = function ($t) use ($p, $q) {
            $t = $t < 0 ? $t + 1 : ($t > 1 ? $t - 1 : $t);

            return $t < 1 / 6 ? $p + ($q - $p) * 6 * $t : ($t < .5 ? $q : ($t < 2 / 3 ? $p + ($q - $p) * (2 / 3 - $t) * 6 : $p));
        };

        return [(int) round($f($h + 1 / 3) * 255), (int) round($f($h) * 255), (int) round($f($h - 1 / 3) * 255)];
    }
}
