<?php

declare(strict_types=1);

namespace Johncms\Modules\Contacts\Application\Services;

use Johncms\Modules\Contacts\Application\DTO\ContactPageDTO;
use Johncms\Modules\Contacts\Application\DTO\ContactSettingsDTO;
use Johncms\Modules\Contacts\Application\DTO\SocialLinkDTO;
use Johncms\System\i18n\Translator;

/**
 * Reads the contact settings stored in the `johncms` config section.
 */
final readonly class ContactSettingsProvider
{
    public function __construct(
        private Translator $translator,
    ) {
    }

    public function getSettings(): ContactSettingsDTO
    {
        $config = config('johncms');

        return new ContactSettingsDTO(
            formEnabled: ! empty($config['contacts_form_enabled']),
            notifyEmail: (string) ($config['contacts_notify_email'] ?? ''),
            email: (string) ($config['contacts_email'] ?? ''),
            phone: (string) ($config['contacts_phone'] ?? ''),
            socials: $this->socialLinks($config['contacts_socials'] ?? []),
            addresses: $this->translatedValues($config['contacts_address'] ?? []),
            workingHours: $this->translatedValues($config['contacts_working_hours'] ?? []),
            texts: $this->translatedValues($config['contacts_text'] ?? []),
        );
    }

    /**
     * Contact information resolved for the current interface language.
     */
    public function getPageData(): ContactPageDTO
    {
        $settings = $this->getSettings();

        return new ContactPageDTO(
            formEnabled: $settings->formEnabled,
            email: $settings->email,
            phone: $settings->phone,
            socials: $settings->socials,
            address: $this->localize($settings->addresses),
            workingHours: $this->localize($settings->workingHours),
            text: $this->localize($settings->texts),
        );
    }

    /**
     * Picks the value for the current language, falling back to the site default language
     * and then to English, so a half-filled setting still shows something meaningful.
     *
     * @param array<string, string> $values
     */
    private function localize(array $values): string
    {
        $languages = [
            $this->translator->getLocale(),
            (string) (config('johncms')['lng'] ?? 'en'),
            'en',
        ];

        foreach ($languages as $language) {
            $value = trim($values[$language] ?? '');
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    /**
     * @param mixed $values
     * @return array<string, string>
     */
    private function translatedValues(mixed $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        $result = [];
        foreach ($values as $language => $value) {
            if (is_string($language) && is_string($value)) {
                $result[$language] = $value;
            }
        }

        return $result;
    }

    /**
     * @param mixed $values
     * @return list<SocialLinkDTO>
     */
    private function socialLinks(mixed $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        $links = [];
        foreach ($values as $value) {
            if (! is_array($value)) {
                continue;
            }

            $title = trim((string) ($value['title'] ?? ''));
            $url = trim((string) ($value['url'] ?? ''));
            if ($title === '' || ! $this->isSafeUrl($url)) {
                continue;
            }

            $links[] = new SocialLinkDTO($title, $url);
        }

        return $links;
    }

    /**
     * Only absolute http(s) links and local paths may be rendered as social links.
     */
    private function isSafeUrl(string $url): bool
    {
        if ($url === '') {
            return false;
        }

        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return true;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        return in_array($scheme, ['http', 'https'], true);
    }
}
