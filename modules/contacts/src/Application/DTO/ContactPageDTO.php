<?php

declare(strict_types=1);

namespace Johncms\Modules\Contacts\Application\DTO;

/**
 * Contact information resolved for a single interface language.
 */
final readonly class ContactPageDTO
{
    /**
     * @param list<SocialLinkDTO> $socials
     */
    public function __construct(
        public bool $formEnabled,
        public string $email,
        public string $phone,
        public array $socials,
        public string $address,
        public string $workingHours,
        public string $text,
    ) {
    }

    /**
     * The phone as a tel: link takes digits and the leading plus only.
     */
    public function phoneHref(): string
    {
        return (string) preg_replace('/[^\d+]/', '', $this->phone);
    }

    public function hasContactInfo(): bool
    {
        return $this->email !== ''
            || $this->phone !== ''
            || $this->address !== ''
            || $this->workingHours !== ''
            || $this->socials !== [];
    }
}
