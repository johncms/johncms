<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

namespace Johncms\Validator\Rules\Legacy;

use Johncms\Security\AntifloodCheckerInterface;
use Laminas\Validator\AbstractValidator;

class Flood extends AbstractValidator
{
    public const FLOOD = 'flood';

    protected $messageTemplates = [
        self::FLOOD => "You cannot add the message so often. Please, wait %value% seconds.",
    ];

    public function isValid($value): bool
    {
        $this->setValue($value);
        $isValid = true;

        $flood_check = di(AntifloodCheckerInterface::class)->getRemainingSeconds();
        if ($flood_check > 0) {
            $this->error(self::FLOOD, $flood_check);
            $isValid = false;
        }

        return $isValid;
    }
}
