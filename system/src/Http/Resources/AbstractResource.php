<?php

declare(strict_types=1);

namespace Johncms\Http\Resources;

abstract class AbstractResource
{
    public function __construct(protected mixed $model)
    {
    }

    public function __get(string $name)
    {
        return $this->model?->$name ?? null;
    }

    public static function createFromCollection(mixed $collection): ResourceCollection
    {
        return new ResourceCollection($collection, static::class);
    }

    abstract public function toArray(): array;
}
