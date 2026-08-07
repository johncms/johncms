<?php

declare(strict_types=1);

namespace Johncms\Mail;

use Gettext\TranslatorFunctions;
use Johncms\System\i18n\Translator;
use Twig\Environment;

/**
 * Renders the body of one email.
 *
 * A message is written in the language of its recipient, so the locale is an argument rather than
 * ambient state: the translator of the process is swapped for the duration of one render and put
 * back afterwards, and a batch of messages in different languages leaves nothing behind.
 */
final readonly class MailRenderer
{
    public function __construct(private Environment $twig)
    {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function render(string $template, array $data, string $locale): string
    {
        $previous = TranslatorFunctions::getTranslator();

        $translator = new Translator();
        $translator->setLocale($locale);
        $translator->addTranslationDomain('system', ROOT_PATH . 'system/locale');
        TranslatorFunctions::register($translator);

        try {
            return $this->twig->render($template, $data + ['locale' => $locale]);
        } finally {
            TranslatorFunctions::register($previous);
        }
    }
}
