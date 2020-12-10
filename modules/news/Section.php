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
use Johncms\Exceptions\PageNotFoundException;
use Johncms\NavChain;
use News\Models\NewsSection;

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

    public function getSections(int $parent = 0): Collection
    {
        return (new NewsSection())->where('parent', $parent)->get();
    }

    /**
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

    public function getLastSection(): ?NewsSection
    {
        if (! empty($this->path)) {
            return $this->path[array_key_last($this->path)];
        }
        return null;
    }
}
