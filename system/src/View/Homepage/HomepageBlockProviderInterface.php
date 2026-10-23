<?php

declare(strict_types=1);

namespace Johncms\View\Homepage;

/**
 * How a module puts something of its own on the home page — the latest news, say — without the
 * home page knowing the module exists.
 *
 * A module implements this, the container tags it, and the home page draws the blocks of the
 * switched-on modules, the lighter ones higher. A module that is switched off has no provider, and
 * its block is simply not there.
 */
interface HomepageBlockProviderInterface
{
    /**
     * @return iterable<HomepageBlock> Nothing when the module has nothing to show.
     */
    public function homepageBlocks(): iterable;
}
