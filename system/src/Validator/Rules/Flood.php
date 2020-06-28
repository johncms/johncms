<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

namespace Johncms\Validator\Rules;

use Johncms\System\Legacy\Tools;
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

        /** @var Tools $tools */
        $tools = di(Tools::class);
        $flood_check = $tools->antiflood();
        if ($flood_check) {
            $this->error(self::FLOOD, $flood_check);
            $isValid = false;
        }

        return $isValid;
    }
}
