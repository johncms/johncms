<?php

declare(strict_types=1);

namespace Johncms\Modules\Consent\Application\View;

use Johncms\Modules\Consent\Application\Services\CookieBannerTextFormatter;
use Johncms\System\i18n\Translator;
use Twig\Extension\RuntimeExtensionInterface;
use Twig\Markup;

/**
 * The state of the cookie banner: whether it is shown, which text it carries and which version of
 * the consent it asks for.
 *
 * The text is written by an administrator, so it is sanitized here and handed over as markup. A
 * banner that is enabled without a text of its own returns none — the layout then prints the
 * translated default.
 */
final readonly class CookieBannerRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private CookieBannerTextFormatter $formatter,
        private Translator $translator,
    ) {
    }

    /**
     * @return null|array{text: ?Markup, version: int}
     */
    public function banner(): ?array
    {
        if (empty(config('johncms.cookie_banner_enabled'))) {
            return null;
        }

        $texts = (array) config('johncms.cookie_banner_text', []);
        $text = (string) ($texts[$this->translator->getLocale()] ?? $texts['en'] ?? '');

        return [
            'text'    => $text === '' ? null : new Markup($this->formatter->toHtml($text), 'UTF-8'),
            'version' => (int) config('johncms.cookie_banner_version', 1),
        ];
    }
}
