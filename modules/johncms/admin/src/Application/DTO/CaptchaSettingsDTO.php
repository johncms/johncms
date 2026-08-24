<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\DTO;

/**
 * What the settings page submitted: which captcha to use, and the settings of each provider.
 */
final readonly class CaptchaSettingsDTO
{
    /**
     * @param array<string, array<string, string>> $providers Raw field values, keyed by provider.
     *                                                        What of it is kept is decided by the
     *                                                        fields the provider declares.
     */
    public function __construct(
        public string $default,
        public array $providers = [],
    ) {
    }
}
