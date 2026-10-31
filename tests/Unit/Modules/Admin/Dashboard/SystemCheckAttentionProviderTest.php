<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Admin\Dashboard;

use Gettext\Translator;
use Gettext\TranslatorFunctions;
use Johncms\Checker\SystemChecker;
use Johncms\Modules\Admin\Application\Dashboard\SystemCheckAttentionProvider;
use Johncms\Modules\Admin\Application\Services\AdminPermissions;
use Johncms\View\Dashboard\AttentionItem;
use Johncms\View\Dashboard\AttentionLevel;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeAccessChecker;

/**
 * The failed checks of PHP, counted by how much they matter, for whoever may open the system check.
 */
final class SystemCheckAttentionProviderTest extends TestCase
{
    protected function setUp(): void
    {
        // Nothing registers a translator in an isolated unit test, and the lines are translated.
        TranslatorFunctions::register(new Translator());
    }

    public function testTheFailedChecksAreCountedByLevel(): void
    {
        $items = $this->itemsFor([AdminPermissions::SYSTEM_CHECK], extensions: [true, true, false], recommendations: [true]);

        self::assertSame(
            [[AttentionLevel::Danger, 2], [AttentionLevel::Warning, 1]],
            array_map(static fn (AttentionItem $item): array => [$item->level, $item->count], $items)
        );
    }

    public function testAServerThatPassesAsksForNothing(): void
    {
        self::assertSame([], $this->itemsFor([AdminPermissions::SYSTEM_CHECK], extensions: [false], recommendations: [false]));
    }

    public function testWhoMayNotOpenTheSystemCheckIsNotToldOfIt(): void
    {
        self::assertSame([], $this->itemsFor([], extensions: [true], recommendations: [true]));
    }

    /**
     * @param list<string> $granted
     * @param list<bool>   $extensions      Whether each critical check fails.
     * @param list<bool>   $recommendations Whether each recommendation fails.
     * @return list<AttentionItem>
     */
    private function itemsFor(array $granted, array $extensions, array $recommendations): array
    {
        $checker = new class ($extensions, $recommendations) extends SystemChecker {
            /**
             * @param list<bool> $extensions
             * @param list<bool> $recommendations
             */
            public function __construct(private array $extensions, private array $recommendations)
            {
            }

            public function checkExtensions(): array
            {
                return array_map(static fn (bool $error): array => ['error' => $error, 'error_level' => self::CRITICAL], $this->extensions);
            }

            public function recommendations(): array
            {
                return array_map(static fn (bool $error): array => ['error' => $error, 'error_level' => self::WARNING], $this->recommendations);
            }
        };

        $provider = new SystemCheckAttentionProvider(new FakeAccessChecker($granted), $checker);

        return [...$provider->attentionItems()];
    }
}
