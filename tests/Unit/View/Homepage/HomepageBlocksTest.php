<?php

declare(strict_types=1);

namespace Tests\Unit\View\Homepage;

use Johncms\View\Homepage\HomepageBlock;
use Johncms\View\Homepage\HomepageBlockProviderInterface;
use Johncms\View\Homepage\HomepageBlockPosition;
use Johncms\View\Homepage\HomepageBlocks;
use PHPUnit\Framework\TestCase;

final class HomepageBlocksTest extends TestCase
{
    public function testTheLighterBlocksGoHigherAndEqualWeightsKeepTheOrderOfTheModules(): void
    {
        $blocks = new HomepageBlocks([
            $this->provider(new HomepageBlock('@shop/offers.twig'), new HomepageBlock('@news/latest.twig', weight: 10)),
            $this->provider(new HomepageBlock('@blog/posts.twig')),
        ]);

        self::assertSame(
            ['@news/latest.twig', '@shop/offers.twig', '@blog/posts.twig'],
            array_map(static fn (HomepageBlock $block): string => $block->template, $blocks->all())
        );
    }

    public function testAModuleWithNothingToShowAddsNoBlock(): void
    {
        self::assertSame([], (new HomepageBlocks([$this->provider()]))->all());
    }

    public function testExistingProvidersStayInContentAndBottomBlocksKeepTheirPosition(): void
    {
        $blocks = new HomepageBlocks([$this->provider(
            new HomepageBlock('@shop/offers.twig'),
            new HomepageBlock('@online/homepage/users.twig', position: HomepageBlockPosition::Bottom),
        )]);

        self::assertSame(HomepageBlockPosition::Content, $blocks->all()[0]->position);
        self::assertSame(HomepageBlockPosition::Bottom, $blocks->all()[1]->position);
    }

    private function provider(HomepageBlock ...$blocks): HomepageBlockProviderInterface
    {
        return new readonly class ($blocks) implements HomepageBlockProviderInterface {
            /**
             * @param list<HomepageBlock> $blocks
             */
            public function __construct(private array $blocks)
            {
            }

            public function homepageBlocks(): iterable
            {
                return $this->blocks;
            }
        };
    }
}
