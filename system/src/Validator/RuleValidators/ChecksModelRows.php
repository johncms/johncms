<?php

declare(strict_types=1);

namespace Johncms\Validator\RuleValidators;

use Closure;
use Illuminate\Database\Eloquent\Model;

/**
 * The lookup both model rules do, told apart only by what they make of the answer.
 */
trait ChecksModelRows
{
    /**
     * @param class-string $model
     * @param Closure|array<string, mixed>|null $exclude
     */
    private function rowExists(string $model, string $field, mixed $value, Closure|array|null $exclude): bool
    {
        /** @var Model $instance */
        $instance = new $model();
        $query = $instance->newQuery();

        if ($exclude instanceof Closure) {
            $query->where($exclude);
        } elseif (is_array($exclude) && ! empty($exclude['field'])) {
            $query->where($exclude['field'], '!=', $exclude['value'] ?? null);
        }

        return $query->where($field, $value)->exists();
    }
}
