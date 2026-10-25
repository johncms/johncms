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
}
