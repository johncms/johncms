<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\News\Application\Menu;

use Gettext\Translator;
use Gettext\TranslatorFunctions;
use Johncms\Modules\Collections\Application\Api\CollectionLookupApiInterface;
use Johncms\Modules\News\Application\Collections\NewsCollectionAvailability;
use Johncms\Modules\News\Application\Collections\NewsReadServiceInterface;
use Johncms\Modules\News\Application\Menu\NewsMenuItemProvider;
use Johncms\Updates\SystemOperationContext;
use Johncms\Updates\SystemUpdateStateInterface;
use Johncms\View\Menu\MenuArea;
use PHPUnit\Framework\TestCase;

final class NewsMenuItemProviderTest extends TestCase
{
    protected function setUp(): void
    {
        TranslatorFunctions::register(new Translator());
    }

    /** The menu is built on every page, including the one that starts the pending update. */
    public function testPendingUpdateKeepsThePublicItemAndReadsNothingFromTheSchema(): void
    {
        $lookup = $this->createMock(CollectionLookupApiInterface::class);
        $lookup->expects(self::never())->method('findDeclared');
        $state = $this->createStub(SystemUpdateStateInterface::class);
        $state->method('isRequiredFor')->willReturn(true);
        $provider = new NewsMenuItemProvider(
            $this->createStub(NewsReadServiceInterface::class),
            new NewsCollectionAvailability($lookup, $state, new SystemOperationContext())
        );

        $items = [...$provider->menuItems()];

        self::assertCount(1, $items);
        self::assertSame(MenuArea::Main, $items[0]->area);
    }
}
