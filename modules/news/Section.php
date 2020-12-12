<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace News;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Johncms\Cache;
use Johncms\Exceptions\PageNotFoundException;
use Johncms\NavChain;
use News\Models\NewsSection;
use Psr\SimpleCache\InvalidArgumentException;

class Section
{
    /** @var NavChain */
    protected $nav_chain;

    /** @var NewsSection[] */
    protected $path = [];

    public function __construct()
    {
        $this->nav_chain = di(NavChain::class);
    }

    /**
     * Get the child sections
     *
     * @param int $parent
     * @return Collection
     */
    public function getSections(int $parent = 0): Collection
    {
        return (new NewsSection())->where('parent', $parent)->get();
    }

    /**
     * Checking the path and adding it to the navigation chain.
     *
     * @param string $category
     * @param bool $set_nav_chain
     * @return NewsSection[]
     */
    public function checkPath(string $category, bool $set_nav_chain = true): array
    {
        if (empty($category)) {
            return $this->path;
        }
        $category = rtrim($category, '/');
        $segments = explode('/', $category);
        $this->path = [];
        $parent = 0;
        foreach ($segments as $item) {
            try {
                $check = (new NewsSection())->where('parent', $parent)->where('code', $item)->firstOrFail();
                $this->path[] = $check;
                $parent = $check->id;
                if ($set_nav_chain) {
                    $this->nav_chain->add($check->name, $check->url);
                }
            } catch (ModelNotFoundException $exception) {
                throw new PageNotFoundException(__('The requested section was not found.'));
            }
        }
        return $this->path;
    }

    /**
     * Getting the last section of the path.
     *
     * @return NewsSection|null
     */
    public function getLastSection(): ?NewsSection
    {
        if (! empty($this->path)) {
            return $this->path[array_key_last($this->path)];
        }
        return null;
    }

    /**
     * Recursively getting subsections.
     *
     * @param NewsSection|null $section
     * @param array $additional_ids
     * @return array
     */
    public function getSubsections(?NewsSection $section, array $additional_ids = []): array
    {
        $ids = $additional_ids;
        if ($section !== null) {
            $subsections = $section->childSections;
            foreach ($subsections as $subsection) {
                $ids[] = $subsection->id;
                $ids = array_merge($ids, $this->getSubsections($subsection));
            }
        }
        return $ids;
    }

    /**
     * Retrieving subsection IDs from the cache.
     *
     * @param NewsSection|null $section
     * @return array
     */
    public function getCachedSubsections(?NewsSection $section): array
    {
        $ids = [];
        if ($section !== null) {
            /** @var Cache $cache */
            $cache = di(Cache::class);
            $ids = $cache->rememberForever(
                'news_subsections',
                function () use ($section) {
                    return [$section->id => $this->getSubsections($section, [$section->id])];
                }
            );

            if (empty($ids) || ! array_key_exists($section->id, $ids)) {
                $this->clearCache();
                $ids = $cache->rememberForever(
                    'news_subsections',
                    function () use ($section) {
                        return [$section->id => $this->getSubsections($section, [$section->id])];
                    }
                );
            }
        }

        return $ids[$section->id ?? 0] ?? [];
    }

    public function clearCache(): void
    {
        /** @var Cache $cache */
        $cache = di(Cache::class);
        try {
            $cache->delete('news_subsections');
        } catch (InvalidArgumentException $e) {
        }
    }
}
