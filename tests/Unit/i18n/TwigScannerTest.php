<?php

declare(strict_types=1);

namespace Tests\Unit\i18n;

use Gettext\Translations;
use Johncms\System\i18n\TwigScanner;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

/**
 * Without this pass the strings of a migrated template drop out of the .pot files, and the
 * translations of every shipped language quietly go with them.
 */
final class TwigScannerTest extends TestCase
{
    private string $file;

    protected function setUp(): void
    {
        $this->file = sys_get_temp_dir() . DS . 'johncms-scan-' . uniqid() . '.twig';
    }

    protected function tearDown(): void
    {
        @unlink($this->file);
    }

    public function testItCollectsTheStringsOfTheDefaultDomainWithTheirLines(): void
    {
        $translations = $this->scan("{{ __('Save') }}\n{{ __('Cancel') }}");

        $save = $translations['system']->find(null, 'Save');
        self::assertNotNull($save);
        self::assertNotNull($translations['system']->find(null, 'Cancel'));
        self::assertSame([1], $save->getReferences()->toArray()[$this->file]);
    }

    public function testAnExplicitDomainSendsTheStringToThatDomain(): void
    {
        $translations = $this->scan("{{ d__('admin', 'Dashboard') }}");

        self::assertNotNull($translations['admin']->find(null, 'Dashboard'));
        self::assertNull($translations['system']->find(null, 'Dashboard'));
    }

    public function testPluralFormsAreCollected(): void
    {
        $translations = $this->scan("{{ n__('One file', '%d files', count) }}");

        $translation = $translations['system']->find(null, 'One file');
        self::assertNotNull($translation);
        self::assertSame('%d files', $translation->getPlural());
    }

    /**
     * A call whose message is built at runtime carries no string to extract. The PHP scanner
     * skips those too — the alternative is a .pot full of variable names.
     */
    public function testACallWithoutALiteralMessageIsSkipped(): void
    {
        $translations = $this->scan("{{ __(title) }}");

        self::assertCount(0, $translations['system']);
    }

    /**
     * @return array<string, Translations>
     */
    private function scan(string $template): array
    {
        file_put_contents($this->file, $template);

        $translations = [
            'system' => Translations::create('system'),
            'admin'  => Translations::create('admin'),
        ];

        $scanner = new TwigScanner($this->environment(), $translations);
        $scanner->setDefaultDomain('system');
        $scanner->scanFile($this->file);

        return $translations;
    }

    private function environment(): Environment
    {
        $twig = new Environment(new ArrayLoader());
        foreach (['__', 'n__', 'd__', 'dn__'] as $name) {
            $twig->addFunction(new \Twig\TwigFunction($name, static fn (): string => ''));
        }

        return $twig;
    }
}
