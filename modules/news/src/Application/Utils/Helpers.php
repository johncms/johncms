<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Utils;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Johncms\Modules\News\Domain\Models\NewsSection;
use Johncms\NavChain;
use Johncms\Security\HTMLPurifier;

class Helpers
{
    /**
     * Метод для построения навигационной цепочки
     */
    public static function buildAdminBreadcrumbs(?NewsSection $parent_section = null): void
    {
        if ($parent_section) {
            /** @var NavChain $nav_chain */
            $nav_chain = di(NavChain::class);

            // Collecting parent sections to build a navigation chain
            $parent_tree = [];
            $parent = $parent_section;
            while ($parent !== null) {
                $parent_tree[] = [
                    'name' => $parent->name,
                    'url'  => '/admin/news/content/' . $parent->id . '/',
                ];
                $parent = $parent->parentSection;
            }

            krsort($parent_tree);
            foreach ($parent_tree as $item) {
                $nav_chain->add($item['name'], $item['url']);
            }
        }
    }

    public static function checkPath(string $category): array
    {
        $category = rtrim($category, '/');
        $segments = explode('/', $category);
        $path = [];
        $parent = 0;
        foreach ($segments as $item) {
            try {
                $check = (new NewsSection())->where('parent', $parent)->where('code', $item)->firstOrFail();
                $path[] = $check;
                $parent = $check->id;
            } catch (ModelNotFoundException $exception) {
                pageNotFound();
            }
        }

        return $path;
    }

    public static function purifyHtml($html): string
    {
        /** @var \HTMLPurifier $purifier */
        $purifier = di(HTMLPurifier::class);
        return $purifier->purify($html);
    }
}
