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

use Exception;
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

    /** @var Cache $cache */
    protected $cache;

    /** @var NewsSection[] */
    protected $path = [];

    public function __construct()
    {
        $this->nav_chain = di(NavChain::class);
        $this->cache = di(Cache::class);
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
     * @psalm-suppress TypeDoesNotContainType, InvalidCatch
     */
    public function getCachedSubsections(?NewsSection $section): array
    {
        $ids = [];
        if ($section !== null) {
            $ids = $this->cache->rememberForever(
                'news_subsections',
                function () use ($section) {
                    return [$section->id => $this->getSubsections($section, [$section->id])];
                }
            );

            if (empty($ids) || ! array_key_exists($section->id, $ids)) {
                try {
                    $this->cache->delete('news_subsections');
                } catch (InvalidArgumentException $exception) {
                }
                $ids = $this->cache->rememberForever(
                    'news_subsections',
                    function () use ($section, $ids) {
                        $ids[$section->id] = $this->getSubsections($section, [$section->id]);
                        return $ids;
                    }
                );
            }
        }

        return $ids[$section->id ?? 0] ?? [];
    }

    /**
     * Getting the full URL of the section from cache.
     *
     * @param int $section_id
     * @return string
     * @psalm-suppress InvalidCatch
     */
    public function getCachedPath(int $section_id): string
    {
        $paths = $this->cache->rememberForever(
            'news_section_paths',
            function () use ($section_id) {
                return [$section_id => $this->getPath($section_id)];
            }
        );

        if (empty($paths) || ! array_key_exists($section_id, $paths)) {
            try {
                $this->cache->delete('news_section_paths');
            } catch (InvalidArgumentException $e) {
            }
            $paths = $this->cache->rememberForever(
                'news_section_paths',
                function () use ($section_id, $paths) {
                    $paths[$section_id] = $this->getPath($section_id);
                    return $paths;
                }
            );
        }
        return $paths[$section_id] ?? '';
    }

    /**
     * Getting the full URL of the section.
     *
     * @param int $id
     * @return string
     */
    public function getPath(int $id): string
    {
        $section_url = '';
        $section = (new NewsSection())->find($id);
        if ($section !== null) {
            $path = [
                $section->code,
            ];
            $parent = $section->parentSection;
            while ($parent !== null) {
                $path[] = $parent->code;
                $parent = $parent->parentSection;
            }
            krsort($path);

            $section_url = implode('/', $path);
        }

        return $section_url;
    }

    /**
     * Clearing the cache.
     *
     * @psalm-suppress InvalidCatch
     */
    public function clearCache(): void
    {
        try {
            $this->cache->delete('news_subsections');
            $this->cache->delete('news_section_paths');
        } catch (InvalidArgumentException $e) {
        }
    }
}
