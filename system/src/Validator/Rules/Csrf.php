<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

namespace Johncms\Validator\Rules;

use Laminas\Validator\AbstractValidator;

class Csrf extends AbstractValidator
{
    public const NOT_SAME = 'notSameSite';

    protected $messageTemplates = [
        self::NOT_SAME => "The form submitted did not originate from the expected site",
    ];

    private $tokenId = \Johncms\Security\Csrf::DEFAULT_TOKEN_ID;

    public function isValid($value): bool
    {
        $this->setValue($value);
        $isValid = true;

        /** @var \Johncms\Security\Csrf $csrf */
        $csrf = di(\Johncms\Security\Csrf::class);

        if ($csrf->getToken($this->tokenId) !== $value) {
            $this->error(self::NOT_SAME);
            $isValid = false;
        }

        return $isValid;
    }

    /**
     * Set token id
     *
     * @param $value
     * @return $this
     */
    public function setTokenId($value): Csrf
    {
        $this->tokenId = $value;
        return $this;
    }
}
