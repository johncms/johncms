<?php

declare(strict_types=1);

namespace Tests\Unit\View\Menu;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\View\Menu\MenuArea;
use Johncms\View\Menu\MenuItem;
use Johncms\View\Menu\MenuItemProviderInterface;
use Johncms\View\Menu\MenuRegistry;
use PHPUnit\Framework\TestCase;

/**
 * How an installed module gets itself into a menu. Without it a module is reachable only by typing
 * its address: both menus are templates of the theme, and a module has no business editing one.
 */
final class MenuRegistryTest extends TestCase
{
    public function testItemsAreReturnedForTheirOwnMenuOnly(): void
    {
        $registry = $this->registry([
            new MenuItem(MenuArea::Main, 'Blog', '/blog/'),
            new MenuItem(MenuArea::Admin, 'Blog settings', '/admin/blog'),
        ]);

        self::assertSame(['Blog'], $this->titles($registry->items(MenuArea::Main)));
        self::assertSame(['Blog settings'], $this->titles($registry->items(MenuArea::Admin)));
    }

    /**
     * The template never has to know which permission stands behind a line: what it is given is
     * already what this visitor may open.
     */
    public function testAnItemTheVisitorMayNotOpenIsNotDrawn(): void
    {
        $registry = $this->registry(
            [
                new MenuItem(MenuArea::Main, 'Blog', '/blog/'),
                new MenuItem(MenuArea::Main, 'Moderation', '/blog/moderate', permission: 'blog.moderate'),
            ],
            allowed: []
        );

        self::assertSame(['Blog'], $this->titles($registry->items(MenuArea::Main)));
    }

    public function testAnItemTheVisitorMayOpenIsDrawn(): void
    {
        $registry = $this->registry(
            [new MenuItem(MenuArea::Main, 'Moderation', '/blog/moderate', permission: 'blog.moderate')],
            allowed: ['blog.moderate']
        );

        self::assertSame(['Moderation'], $this->titles($registry->items(MenuArea::Main)));
    }

    /**
     * Lighter items float up, and equal weights fall back to the title — so two modules that never
     * heard of each other still produce the same menu on every request.
     */
    public function testItemsAreOrderedByWeightAndThenByTitle(): void
    {
        $registry = $this->registry([
            new MenuItem(MenuArea::Main, 'Shop', '/shop/', weight: 100),
            new MenuItem(MenuArea::Main, 'Blog', '/blog/', weight: 100),
            new MenuItem(MenuArea::Main, 'Downloads', '/files/', weight: 10),
        ]);

        self::assertSame(['Downloads', 'Blog', 'Shop'], $this->titles($registry->items(MenuArea::Main)));
    }

    public function testAnIconIsEitherASpriteIdOrAnAddress(): void
    {
        self::assertFalse((new MenuItem(MenuArea::Main, 'Blog', '/blog/', icon: 'book'))->hasImageIcon());
        self::assertTrue(
            (new MenuItem(MenuArea::Main, 'Blog', '/blog/', icon: '/modules/blog/img/icon.svg'))->hasImageIcon()
        );
    }

    /**
     * The registry serves one visitor at a time and is shared between requests, so what it worked
     * out for one of them must not be handed to the next.
     */
    public function testWhatWasWorkedOutForOneVisitorIsNotKept(): void
    {
        $allow = false;
        $access = $this->createStub(AccessCheckerInterface::class);
        $access->method('allows')->willReturnCallback(static function () use (&$allow): bool {
            return $allow;
        });

        $registry = new MenuRegistry(
            [$this->provider([new MenuItem(MenuArea::Main, 'Moderation', '/m', permission: 'blog.moderate')])],
            $access
        );

        self::assertSame([], $registry->items(MenuArea::Main));

        $allow = true;
        $registry->reset();

        self::assertSame(['Moderation'], $this->titles($registry->items(MenuArea::Main)));
    }

    /**
     * @param list<MenuItem>    $items
     * @param list<string>|null $allowed Permissions this visitor holds; null allows everything.
     */
    private function registry(array $items, ?array $allowed = null): MenuRegistry
    {
        $access = $this->createStub(AccessCheckerInterface::class);
        $access->method('allows')->willReturnCallback(
            static fn (string $permission): bool => $allowed === null || in_array($permission, $allowed, true)
        );

        return new MenuRegistry([$this->provider($items)], $access);
    }

    /**
     * @param list<MenuItem> $items
     */
    private function provider(array $items): MenuItemProviderInterface
    {
        return new class ($items) implements MenuItemProviderInterface {
            /** @param list<MenuItem> $items */
            public function __construct(private readonly array $items)
            {
            }

            public function menuItems(): iterable
            {
                return $this->items;
            }
        };
    }

    /**
     * @param list<MenuItem> $items
     * @return list<string>
     */
    private function titles(array $items): array
    {
        return array_map(static fn (MenuItem $item): string => $item->title, $items);
    }
}
