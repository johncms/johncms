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

use Johncms\Http\Session;
use Laminas\Validator\AbstractValidator;

class Captcha extends AbstractValidator
{
    public const CAPTCHA = 'captcha';
    public const SESSION_FIELD = 'code';

    protected array $messageTemplates = [
        self::CAPTCHA => "The security code is not correct",
    ];

    public function isValid($value): bool
    {
        $this->setValue($value);
        $isValid = true;
        $code = di(Session::class)->get(Captcha::SESSION_FIELD);
        if (empty($code) || strtolower($code) !== strtolower($value)) {
            $this->error(self::CAPTCHA);
            $isValid = false;
        }

        return $isValid;
    }
}
