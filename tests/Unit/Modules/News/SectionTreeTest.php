<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\News;

use Johncms\Modules\News\Domain\Services\SectionTree;
use PHPUnit\Framework\TestCase;

final class SectionTreeTest extends TestCase
{
    public function testASubtreeHoldsTheSectionAndEverythingUnderIt(): void
    {
        $tree = new SectionTree([1 => 0, 2 => 1, 3 => 2, 4 => 1, 5 => 0]);

        self::assertSame([1, 2, 4, 3], $tree->subtreeOf(1));
        self::assertSame([2, 3], $tree->subtreeOf(2));
        self::assertSame([5], $tree->subtreeOf(5));
    }

    public function testAnUnknownSectionIsItsOwnSubtree(): void
    {
        self::assertSame([9], (new SectionTree([1 => 0]))->subtreeOf(9));
    }

    public function testALoopInTheDataEnds(): void
    {
        $tree = new SectionTree([1 => 2, 2 => 1]);

        self::assertSame([1, 2], $tree->subtreeOf(1));
    }

    public function testAPathRunsFromTheTopDown(): void
    {
        $tree = new SectionTree([1 => 0, 2 => 1, 3 => 2], [1 => 'Releases', 2 => '9.x', 3 => '9.9']);

        self::assertSame(['Releases', '9.x', '9.9'], $tree->pathOf(3));
        self::assertSame([], $tree->pathOf(0));
    }

    public function testTheAncestorsRunFromTheParentUpAndGiveTheDepth(): void
    {
        $tree = new SectionTree([1 => 0, 2 => 1, 3 => 2]);

        self::assertSame([2, 1], $tree->ancestorsOf(3));
        self::assertSame([], $tree->ancestorsOf(1));
        self::assertSame(2, $tree->depthOf(3));
        self::assertSame(0, $tree->depthOf(1));
    }

    public function testTheAncestorsOfALoopEnd(): void
    {
        self::assertSame([2], (new SectionTree([1 => 2, 2 => 1]))->ancestorsOf(1));
    }

    public function testTheOrderPutsEverySectionUnderItsParentByName(): void
    {
        $tree = new SectionTree([1 => 0, 2 => 1, 3 => 0, 4 => 1, 5 => 99], [1 => 'Releases', 2 => 'b', 3 => 'Guides', 4 => 'a', 5 => 'Lost']);

        // A section whose parent is gone comes last.
        self::assertSame([3, 1, 4, 2, 5], $tree->ordered());
        self::assertSame([1, 3], $tree->childrenOf(0));
    }
}
