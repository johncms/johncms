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

class Captcha extends AbstractValidator
{
    public const CAPTCHA = 'captcha';

    protected $messageTemplates = [
        self::CAPTCHA => "The security code is not correct",
    ];

    /**
     * @var string
     */
    private $sessionField = 'code';

    public function isValid($value): bool
    {
        $this->setValue($value);
        $isValid = true;

        if (
            ! isset($_SESSION[$this->sessionField]) ||
            empty($_SESSION[$this->sessionField]) ||
            strtolower($_SESSION[$this->sessionField]) !== strtolower($value)
        ) {
            $this->error(self::CAPTCHA);
            $isValid = false;
        }

        return $isValid;
    }

    /**
     * Set the session field name
     *
     * @param $value
     * @return $this
     */
    public function setSessionField($value): Captcha
    {
        $this->sessionField = $value;
        return $this;
    }
}
