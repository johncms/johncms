<?php

declare(strict_types=1);

namespace Johncms\Mail;

use Gettext\TranslatorFunctions;
use Gettext\TranslatorInterface;
use Johncms\System\i18n\Translator;
use Symfony\Component\Mime\HtmlToTextConverter\HtmlToTextConverterInterface;
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
    public function __construct(
        private Environment $twig,
        private HtmlToTextConverterInterface $htmlToText,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function render(string $template, array $data, string $locale): RenderedEmailDTO
    {
        $previous = self::currentTranslator();

        $translator = new Translator();
        $translator->setLocale($locale);
        $translator->addTranslationDomain(Translator::SYSTEM_DOMAIN, ROOT_PATH . 'system/locale');
        TranslatorFunctions::register($translator);

        $context = $data + ['locale' => $locale];

        try {
            $html = $this->twig->render($template, $context);

            return new RenderedEmailDTO($html, $this->text($template, $context, $html));
        } finally {
            if ($previous !== null) {
                TranslatorFunctions::register($previous);
            }
        }
    }

    /**
     * The plain text alternative of a message.
     *
     * A message carrying only HTML looks like bulk mail to a spam filter and is unreadable to a
     * client that shows text. The wording of the text form is worth writing by hand, so a template
     * of its own is used when the theme provides one — `registration.txt.twig` next to
     * `registration.twig` — and only otherwise is the text derived from the rendered HTML.
     *
     * @param array<string, mixed> $context
     */
    private function text(string $template, array $context, string $html): string
    {
        $textTemplate = preg_replace('/\.twig$/', '.txt.twig', $template);

        if ($textTemplate !== null && $textTemplate !== $template && $this->twig->getLoader()->exists($textTemplate)) {
            return trim($this->twig->render($textTemplate, $context));
        }

        return self::tidy($this->htmlToText->convert($html, 'utf-8'));
    }

    /**
     * The converter works on the markup and leaves the blank lines the markup had; what a person
     * reads should not open with a screen of them.
     */
    private static function tidy(string $text): string
    {
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace('/ ?\R/', "\n", $text) ?? $text;
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;

        return trim($text);
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
