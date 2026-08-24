<?php

declare(strict_types=1);

namespace Johncms\Modules\Contacts\Application\DTO;

/**
 * Raw contact settings as they are stored in the system config.
 *
 * Text fields are multilingual: they are keyed by language code.
 */
final readonly class ContactSettingsDTO
{
    /**
     * @param list<SocialLinkDTO> $socials
     * @param array<string, string> $addresses
     * @param array<string, string> $workingHours
     * @param array<string, string> $texts
     */
    public function __construct(
        public bool $formEnabled,
        public string $notifyEmail,
        public string $email,
        public string $phone,
        public array $socials,
        public array $addresses,
        public array $workingHours,
        public array $texts,
    ) {
    }
}
