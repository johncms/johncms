<?php

declare(strict_types=1);

namespace Johncms\Utility;

class Numbers
{
    public static function formatNumber(int|float $number): float|int|string
    {
        $prefixes = 'KMGTPEZY';
        if ($number >= 1000) {
            for ($i = -1; $number >= 1000; ++$i) {
                $number /= 1000;
            }
            if ($number > 100) {
                $number = floor($number);
            }
            return round($number, 2) . $prefixes[$i];
        }
        return $number;
    }
}
