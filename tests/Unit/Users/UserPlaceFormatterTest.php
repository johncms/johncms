<?php

declare(strict_types=1);

namespace Tests\Unit\Users;

use Gettext\Translator;
use Gettext\TranslatorFunctions;
use Johncms\Users\UserPlaceFormatter;
use Johncms\Users\VisitorPlaceProviderInterface;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeAccessChecker;
use Twig\Markup;

final class UserPlaceFormatterTest extends TestCase
{
    protected function setUp(): void
    {
        // The dictionary of places is written with d__().
        TranslatorFunctions::register(new Translator());
    }

    public function testAModuleNamesThePlacesOfItsOwnBeforeTheDictionaryIsAsked(): void
    {
        $formatter = new UserPlaceFormatter(new FakeAccessChecker(), [
            $this->provider('/forum', 'In the topic of the forum'),
        ]);

        self::assertSame('In the topic of the forum', (string) $formatter->format('/forum/editors/tabs-1/'));
    }

    public function testAPlaceNoModuleClaimsIsLookedUpInTheDictionary(): void
    {
        $formatter = new UserPlaceFormatter(new FakeAccessChecker(), [
            $this->provider('/forum', 'In the topic of the forum'),
        ]);

        self::assertStringContainsString('/news/"', (string) $formatter->format('/news'));
    }

    private function provider(string $prefix, string $name): VisitorPlaceProviderInterface
    {
        return new readonly class ($prefix, $name) implements VisitorPlaceProviderInterface {
            public function __construct(private string $prefix, private string $name)
            {
            }

            public function describe(string $place): ?Markup
            {
                return str_starts_with($place, $this->prefix) ? new Markup($this->name, 'UTF-8') : null;
            }
        };
    }
}
