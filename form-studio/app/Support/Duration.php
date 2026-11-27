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
}
