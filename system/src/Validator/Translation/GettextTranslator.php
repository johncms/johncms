<?php

declare(strict_types=1);

namespace Johncms\Validator\Translation;

use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Feeds the validator its messages from the gettext catalogs of the project.
 *
 * The built-in catalogs of symfony/validator are not loaded at all. Three reasons: the msgids of
 * the previous engine are already translated into twenty languages here; the scanner only
 * extracts literal d__() calls from the sources of the project, so a default living inside
 * vendor/ would never reach the .pot; and some Symfony messages carry pipe pluralization, which
 * a naive substitution would print to the page with the pipe still in it.
 *
 * So the rules keep the msgids the project already has, and this is where they are translated.
 */
final readonly class GettextTranslator implements TranslatorInterface
{
    private const DOMAIN = 'system';

    /**
     * @param array<string, mixed> $parameters
     */
    public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        return strtr(d__($domain ?? self::DOMAIN, $id), $this->placeholders($parameters));
    }

    public function getLocale(): string
    {
        return locale_get_default();
    }

    /**
     * Symfony names its parameters {{ limit }}, {{ value }} and so on, while the msgids of this
     * project use the %min%, %max% and %value% of the previous engine. Both spellings are filled
     * in, so a message keeps working whether it came with a rule of ours or with a constraint of
     * the vendor.
     *
     * {{ limit }} feeds both %min% and %max%: a Length message names one bound or the other, and
     * never both at once.
     *
     * @param array<string, mixed> $parameters
     * @return array<string, string>
     */
    private function placeholders(array $parameters): array
    {
        $placeholders = [];

        foreach ($parameters as $name => $value) {
            $value = is_scalar($value) ? (string) $value : '';
            $bare = trim($name, '{} %');

            $placeholders[$name] = $value;
            $placeholders['%' . $bare . '%'] = $value;

            if ($bare === 'limit') {
                $placeholders['%min%'] = $value;
                $placeholders['%max%'] = $value;
            }
        }

        return $placeholders;
    }
}
