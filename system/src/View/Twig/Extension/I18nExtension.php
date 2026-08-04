<?php

declare(strict_types=1);

namespace Johncms\View\Twig\Extension;

use Gettext\TranslatorFunctions;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * The translation helpers, under the names they already have in PHP code.
 *
 * A template uses the default domain, which the controller sets — the same rule as everywhere
 * else, so a string reads identically wherever it lives.
 *
 * The translator is reached through the gettext facade rather than by calling the __() helpers:
 * the string scanner reads this file too, and a call whose message is a variable is not something
 * it can extract. Behaviour is the same, these are the bodies of those helpers.
 */
final class I18nExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('__', $this->gettext(...)),
            new TwigFunction('n__', $this->ngettext(...)),
            new TwigFunction('d__', $this->dgettext(...)),
            new TwigFunction('dn__', $this->dngettext(...)),
        ];
    }

    public function gettext(string $original, mixed ...$args): string
    {
        return $this->format(TranslatorFunctions::getTranslator()->gettext($original), $args);
    }

    public function ngettext(string $original, string $plural, int $value, mixed ...$args): string
    {
        return $this->format(TranslatorFunctions::getTranslator()->ngettext($original, $plural, $value), $args);
    }

    public function dgettext(string $domain, string $original, mixed ...$args): string
    {
        return $this->format(TranslatorFunctions::getTranslator()->dgettext($domain, $original), $args);
    }

    public function dngettext(string $domain, string $original, string $plural, int $value, mixed ...$args): string
    {
        return $this->format(
            TranslatorFunctions::getTranslator()->dngettext($domain, $original, $plural, $value),
            $args
        );
    }

    /**
     * @param array<mixed> $args
     */
    private function format(string $text, array $args): string
    {
        return TranslatorFunctions::getFormatter()->format($text, $args);
    }
}
