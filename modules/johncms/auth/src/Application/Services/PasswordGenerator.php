<?php

declare(strict_types=1);

namespace Johncms\Modules\Auth\Application\Services;

final readonly class PasswordGenerator
{
    private const ALPHABET = 'abcdefghijklmnopqrstuvwxyz0123456789';

    public function generate(int $length): string
    {
        $maxIndex = strlen(self::ALPHABET) - 1;
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= self::ALPHABET[random_int(0, $maxIndex)];
        }

        return $result;
    }
}
