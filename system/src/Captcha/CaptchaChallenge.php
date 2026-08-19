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
 * Everything a form needs to show a captcha, whichever provider produced it.
 *
 * The template comes from the provider rather than from the page: a module shipping a provider
 * ships its widget with it, and the pages include one component that knows nothing about which
 * provider answered.
 */
final readonly class CaptchaChallenge
{
    /**
     * @param array<string, mixed> $params What the template of this provider reads: the picture
     *                                     of the built-in one, the site key of a remote service.
     */
    public function __construct(
        public string $provider,
        public string $template,
        public string $fieldName,
        public array $params = [],
    ) {
    }
}
