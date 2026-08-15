<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\DTO;

final readonly class SystemSettingsDTO
{
    public function __construct(
        public string $theme,
        public string $email,
        public int $timeShift,
        public string $copyright,
        public string $homeUrl,
        public int $maxFileSize,
        public int $gzip,
        public string $metaTitle,
        public string $metaKeywords,
        public string $metaDescription,
        public int $userEmailRequired,
        public int $userEmailConfirmation,
        public string $privacyPolicyUrl,
        public string $termsOfUseUrl,
        public string $personalDataPolicyUrl,
        public string $cookiePolicyUrl,
        // Switches of the modules that are not permissions of anybody: whether a module has
        // comments at all, and whether a new account waits for an administrator.
        public bool $libraryComments,
        public bool $downloadsComments,
        public bool $registrationModeration,
    ) {
    }
}
