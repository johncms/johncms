<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Captcha;

/**
 * The verdict of a provider on one answer.
 */
final readonly class CaptchaResult
{
    private function __construct(
        public bool $passed,
        public ?CaptchaFailure $failure = null,
    ) {
    }

    public static function passed(): self
    {
        return new self(true);
    }

    public static function failed(CaptchaFailure $failure): self
    {
        return new self(false, $failure);
    }
}
