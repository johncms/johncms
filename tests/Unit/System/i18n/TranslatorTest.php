<?php

declare(strict_types=1);

namespace Tests\Unit\System\i18n;

use Johncms\System\i18n\Translator;
use PHPUnit\Framework\TestCase;

final class TranslatorTest extends TestCase
{
    private string $localesRoot;

    protected function setUp(): void
    {
        $this->localesRoot = sys_get_temp_dir() . '/johncms-translator-' . bin2hex(random_bytes(4));

        $this->writeDomain('first', 'ru', ['Forum' => 'Форум']);
        $this->writeDomain('first', 'en', ['Forum' => 'Forum']);
        $this->writeDomain('second', 'ru', ['Forum' => 'Форум второго домена']);
        $this->writeDomain('second', 'en', ['Forum' => 'Forum of the second domain']);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->localesRoot . '/*/*') ?: [] as $file) {
            unlink($file);
        }

        foreach (glob($this->localesRoot . '/*') ?: [] as $directory) {
            rmdir($directory);
        }

        rmdir($this->localesRoot);
    }

    public function testTranslationsFollowTheLocaleTheDomainWasRegisteredUnder(): void
    {
        $translator = $this->makeTranslator('ru');

        self::assertSame('Форум', $translator->gettext('Forum'));
    }

    public function testChangingTheLocaleReloadsTheRegisteredDomains(): void
    {
        // Domains are registered once per process (from the constructors of the shared
        // controllers) while the locale belongs to the request: without the reload the visitor
        // who asked for English would be answered in the language of the previous one.
        $translator = $this->makeTranslator('ru');

        $translator->setLocale('en');

        self::assertSame('en', $translator->getLocale());
        self::assertSame('Forum', $translator->gettext('Forum'));
    }

    public function testTheDefaultDomainSurvivesTheReload(): void
    {
        $translator = $this->makeTranslator('ru');
        $translator->addTranslationDomain('second', $this->localesRoot . '/second', false);
        $translator->defaultDomain('second');

        $translator->setLocale('en');

        self::assertSame('Forum of the second domain', $translator->gettext('Forum'));
    }

    public function testTheLocaleCanBeChangedBeforeAnyDomainIsRegistered(): void
    {
        $translator = new Translator();
        $translator->setLocale('en');
        $translator->addTranslationDomain('first', $this->localesRoot . '/first');

        self::assertSame('Forum', $translator->gettext('Forum'));
    }

    private function makeTranslator(string $locale): Translator
    {
        $translator = new Translator();
        $translator->setLocale($locale);
        $translator->addTranslationDomain('first', $this->localesRoot . '/first');

        return $translator;
    }

    /**
     * @param array<string, string> $messages
     */
    private function writeDomain(string $domain, string $locale, array $messages): void
    {
        $path = $this->localesRoot . '/' . $domain;

        if (! is_dir($path)) {
            mkdir($path, 0o777, true);
        }

        $translations = [
            'domain'   => $domain,
            'messages' => ['' => $messages],
        ];

        file_put_contents(
            $path . '/' . $locale . '.lng.php',
            '<?php return ' . var_export($translations, true) . ';'
        );
    }
}
