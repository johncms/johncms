<?php

declare(strict_types=1);

namespace Johncms\Http\Resources;

abstract class AbstractResource
{
    protected mixed $model;

    public function __construct(mixed $model)
    {
        $this->model = $model;
    }

    public static function createFromCollection(mixed $collection): ResourceCollection
    {
        return new ResourceCollection($collection, static::class);
    }

    abstract public function toArray(): array;
}
