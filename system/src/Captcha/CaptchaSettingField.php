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
 * One setting of a provider, as the panel has to draw it.
 */
final readonly class CaptchaSettingField
{
    /**
     * @param array<string, string> $options Values of a Select field, as value => label.
     */
    public function __construct(
        public string $key,
        public CaptchaSettingType $type,
        public string $label,
        public string|int|float|bool $default = '',
        public string $hint = '',
        public array $options = [],
    ) {
    }
}
