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

use Johncms\Users\User;
use Laminas\Validator\AbstractValidator;

class Ban extends AbstractValidator
{
    public const BAN = 'ban';

    protected array $messageTemplates = [
        self::BAN => "You have a ban",
    ];

    private array $bans = [];

    public function isValid($value): bool
    {
        $this->setValue($value);
        $isValid = true;

        $user = di(User::class);
        if ($user?->hasBan($this->bans)) {
            $this->error(self::BAN);
            $isValid = false;
        }

        return $isValid;
    }

    /**
     * Set bans to check
     */
    public function setBans(array $value): Ban
    {
        $this->bans = $value;
        return $this;
    }
}
