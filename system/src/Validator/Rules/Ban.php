<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

namespace Johncms\Validator\Rules;

use Johncms\Users\User;
use Laminas\Validator\AbstractValidator;

class Ban extends AbstractValidator
{
    public const BAN = 'ban';

    protected $messageTemplates = [
        self::BAN => "You have a ban",
    ];

    /**
     * @var array
     */
    private $bans = [1];

    public function isValid($value): bool
    {
        $this->setValue($value);
        $isValid = true;

        /** @var User $user */
        $user = di(User::class);

        foreach ($this->bans as $ban) {
            if (array_key_exists($ban, $user->ban)) {
                $this->error(self::BAN);
                $isValid = false;
            }
        }

        return $isValid;
    }

    /**
     * Set bans to check
     *
     * @param $value
     * @return $this
     */
    public function setBans(array $value): Ban
    {
        $this->bans = $value;
        return $this;
    }
}
