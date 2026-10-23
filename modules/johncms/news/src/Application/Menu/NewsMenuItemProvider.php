<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Menu;

use Johncms\Modules\News\Domain\Models\NewsArticle;
use Johncms\View\Menu\MenuArea;
use Johncms\View\Menu\MenuItem;
use Johncms\View\Menu\MenuItemProviderInterface;

/**
 * The news in the main menu, with the articles of the last days the site counts as new — as many
 * days as the news are kept on the home page; none when that is switched off.
 */
final readonly class NewsMenuItemProvider implements MenuItemProviderInterface
{
    public function menuItems(): iterable
    {
        yield new MenuItem(
            MenuArea::Main,
            d__('news', 'News archive'),
            '/news/',
            icon: 'book',
            weight: 10,
            counter: static function (): int {
                $days = (int) (config('news')['homepage_days'] ?? 0);

                return $days > 0 ? NewsArticle::query()->scopes(['active', 'lastDays' => [$days]])->count() : 0;
            },
        );
    }
}
