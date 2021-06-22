<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Guestbook\Resources;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class ResourceCollection
{
    /**
     * @var Collection
     */
    protected $collection;

    /**
     * @var Collection|LengthAwarePaginator
     */
    protected $original;

    /**
     * @var string
     */
    protected $resource;

    /**
     * ResourceCollection constructor.
     *
     * @param Collection|LengthAwarePaginator $collection
     * @param string $resource
     * @psalm-suppress RedundantConditionGivenDocblockType
     */
    public function __construct($collection, string $resource)
    {
        $this->original = $collection;
        $this->resource = $resource;

        if (! is_subclass_of($resource, BaseResource::class)) {
            throw new InvalidArgumentException(sprintf("The '%s' class must be a subclass of '%s'.", $resource, BaseResource::class));
        }

        if ($collection instanceof Collection) {
            $this->collection = $collection;
        } elseif ($collection instanceof LengthAwarePaginator) {
            $this->collection = $collection->getItems();
        } else {
            $this->collection = Collection::make();
        }
    }

    /**
     * @return array
     * @psalm-suppress MissingClosureParamType, MissingClosureReturnType, InvalidStringClass
     */
    protected function prepare(): array
    {
        return $this->collection->map(
            function ($value) {
                return (new $this->resource($value))->toArray();
            }
        )->toArray();
    }

    public function paginate(): array
    {
        return [
            'current_page'   => $this->original->currentPage(),
            'data'           => $this->prepare(),
            'first_page_url' => $this->original->url(1),
            'from'           => $this->original->firstItem(),
            'last_page'      => $this->original->lastPage(),
            'last_page_url'  => $this->original->url($this->original->lastPage()),
            'next_page_url'  => $this->original->nextPageUrl(),
            'path'           => $this->original->path(),
            'per_page'       => $this->original->perPage(),
            'prev_page_url'  => $this->original->previousPageUrl(),
            'to'             => $this->original->lastItem(),
            'total'          => $this->original->total(),
        ];
    }

    public function toArray(): array
    {
        if ($this->original instanceof LengthAwarePaginator) {
            return $this->paginate();
        }
        return $this->prepare();
    }
}
