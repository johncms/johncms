<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Validator\Rules;

use Johncms\Security\AntiFlood;
use Laminas\Validator\AbstractValidator;

class Flood extends AbstractValidator
{
    public const FLOOD = 'flood';

    protected array $messageTemplates = [
        self::FLOOD => "You cannot add the message so often. Please, wait %value% seconds.",
    ];

    public function isValid($value): bool
    {
        $this->setValue($value);
        $isValid = true;
        $antiFlood = di(AntiFlood::class);
        $floodCheck = $antiFlood->check();
        if ($floodCheck) {
            $this->error(self::FLOOD, $floodCheck);
            $isValid = false;
        }

        return $isValid;
    }
}
