<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Collections\Domain;

use Johncms\Modules\Collections\Domain\Services\SectionTree;
use PHPUnit\Framework\TestCase;

final class SectionTreeTest extends TestCase
{
    public function testTheOrderPutsEverySectionUnderItsParentBySortThenName(): void
    {
        $tree = new SectionTree(
            [1 => 0, 2 => 1, 3 => 1, 4 => 0, 5 => 99],
            [1 => 'Tech', 2 => 'PHP', 3 => 'JavaScript', 4 => 'Life', 5 => 'Lost'],
            [1 => 100, 2 => 100, 3 => 100, 4 => 50, 5 => 100],
        );

        // Life sorts first by its number; JavaScript before PHP by name at the same number.
        self::assertSame([4, 1, 3, 2, 5], $tree->ordered());
    }

    public function testPathsDepthsAndCodesRunFromTheTop(): void
    {
        $tree = new SectionTree([1 => 0, 2 => 1, 3 => 2], [1 => 'Tech', 2 => 'PHP', 3 => 'Laravel'], [], [1 => 'tech', 2 => 'php', 3 => 'laravel']);

        self::assertSame(['Tech', 'PHP', 'Laravel'], $tree->pathOf(3));
        self::assertSame(['tech', 'php', 'laravel'], $tree->codePathOf(3));
        self::assertSame(2, $tree->depthOf(3));
        self::assertSame([1, 2, 3], $tree->subtreeOf(1));
        self::assertSame([], $tree->pathOf(42));
    }

    public function testALoopInTheDataEnds(): void
    {
        $tree = new SectionTree([1 => 2, 2 => 1]);

        self::assertSame([1, 2], $tree->subtreeOf(1));
        self::assertSame([2], $tree->ancestorsOf(1));
    }
}
