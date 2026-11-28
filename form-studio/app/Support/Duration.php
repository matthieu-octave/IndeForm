<?php

namespace App\Support;

class Duration
{
    /** 135 → "2 h 15", 45 → "45 min", 120 → "2 h". */
    public static function format(int $minutes): string
    {
        if ($minutes < 60) {
            return $minutes.' min';
        }
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        return $m === 0 ? "{$h} h" : sprintf('%d h %02d', $h, $m);
    }

    /** Écart signé : +15 min, −1 h 05, « à l’heure ». */
    public static function delta(int $minutes): string
    {
        if ($minutes === 0) {
            return 'à l’heure';
        }

        return ($minutes > 0 ? '+' : '−').self::format(abs($minutes));
    }

    /** "09:00" + 135 min → "11:15" (modulo 24 h). */
    public static function clock(string $start, int $offsetMinutes): string
    {
        [$h, $m] = array_map('intval', explode(':', $start) + [1 => 0]);
        $total = (($h * 60 + $m + $offsetMinutes) % 1440 + 1440) % 1440;

        return sprintf('%02d:%02d', intdiv($total, 60), $total % 60);
    }
}
