<?php

declare(strict_types=1);

namespace Johncms\Mail;

use Gettext\TranslatorFunctions;
use Gettext\TranslatorInterface;
use Johncms\System\i18n\Translator;
use Twig\Environment;
use TypeError;

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
        $previous = self::currentTranslator();

        $translator = new Translator();
        $translator->setLocale($locale);
        $translator->addTranslationDomain('system', ROOT_PATH . 'system/locale');
        TranslatorFunctions::register($translator);

        try {
            return $this->twig->render($template, $data + ['locale' => $locale]);
        } finally {
            if ($previous !== null) {
                TranslatorFunctions::register($previous);
            }
        }
    }

    /**
     * The translator in force, or null when nothing registered one yet.
     *
     * The library has no way to ask: its getter is typed against the interface and raises a
     * TypeError on the null it holds until the first register(). A process that renders a message
     * without having gone through the usual bootstrap — a worker, a test — must not die of that.
     */
    private static function currentTranslator(): ?TranslatorInterface
    {
        try {
            return TranslatorFunctions::getTranslator();
        } catch (TypeError) {
            return null;
        }
    }
}
