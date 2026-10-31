<?php

declare(strict_types=1);

namespace Tests\Unit\View\Dashboard;

use Johncms\View\Dashboard\AttentionItem;
use Johncms\View\Dashboard\AttentionLevel;
use Johncms\View\Dashboard\DashboardAttention;
use Johncms\View\Dashboard\DashboardAttentionProviderInterface;
use PHPUnit\Framework\TestCase;

final class DashboardAttentionTest extends TestCase
{
    public function testTheMostPressingLinesGoFirstAndEqualLevelsKeepTheOrderOfTheModules(): void
    {
        $attention = new DashboardAttention([
            $this->provider($this->item('/shop/orders', AttentionLevel::Pending), $this->item('/admin/system_check', AttentionLevel::Warning)),
            $this->provider($this->item('/blog/comments', AttentionLevel::Pending), $this->item('/admin/disk', AttentionLevel::Danger)),
        ]);

        self::assertSame(
            ['/admin/disk', '/admin/system_check', '/shop/orders', '/blog/comments'],
            array_map(static fn (AttentionItem $item): string => $item->url, $attention->all())
        );
    }

    public function testAModuleWithNothingToAskAddsNoLine(): void
    {
        self::assertSame([], (new DashboardAttention([$this->provider()]))->all());
    }

    private function item(string $url, AttentionLevel $level): AttentionItem
    {
        return new AttentionItem('Line', $url, $level, 1);
    }

    private function provider(AttentionItem ...$items): DashboardAttentionProviderInterface
    {
        return new readonly class ($items) implements DashboardAttentionProviderInterface {
            /**
             * @param list<AttentionItem> $items
             */
            public function __construct(private array $items)
            {
            }

            public function attentionItems(): iterable
            {
                return $this->items;
            }
        };
    }
}
