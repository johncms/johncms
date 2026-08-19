<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\DTO;

/**
 * One captcha provider on the settings page: the built-in picture, a service, or whatever a
 * module registered.
 */
final readonly class CaptchaProviderRowDTO
{
    /**
     * @param bool                      $isActive     The provider the forms currently use.
     * @param bool                      $isConfigured Whether it has what it needs to work. An
     *                                                unconfigured one can be filled in here but is
     *                                                not put in front of visitors.
     * @param list<CaptchaSettingRowDTO> $fields
     */
    public function __construct(
        public string $key,
        public string $label,
        public bool $isActive,
        public bool $isConfigured,
        public array $fields,
    ) {
    }
}
