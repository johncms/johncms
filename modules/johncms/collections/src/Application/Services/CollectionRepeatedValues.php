<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Services;

use InvalidArgumentException;

final readonly class CollectionRepeatedValues
{
    /** @return array{string, string, int} */
    public function action(string $raw): array
    {
        if (! preg_match('/^([a-z0-9_-]+):(add|remove|up|down):([0-9]{1,4})$/D', $raw, $parts)) {
            throw new InvalidArgumentException(d__('collections', 'Choose an existing value and a valid action.'));
        }
        return [$parts[1], $parts[2], (int) $parts[3]];
    }

    /** @param list<string> $values
     * @return list<string>
     */
    public function apply(array $values, string $operation, int $index, int $limit = 1000): array
    {
        if ($operation === 'add') {
            if (count($values) >= $limit) {
                throw new InvalidArgumentException(d__('collections', 'The field has too many values.'));
            }
            return [...$values, ''];
        }
        if (! array_key_exists($index, $values)) {
            throw new InvalidArgumentException(d__('collections', 'Choose an existing value and a valid action.'));
        }
        if ($operation === 'remove') {
            array_splice($values, $index, 1);
            return $values;
        }
        $target = match ($operation) {
            'up' => $index - 1, 'down' => $index + 1, default => -1
        };
        if (! array_key_exists($target, $values)) {
            throw new InvalidArgumentException(d__('collections', 'Choose an existing value and a valid action.'));
        }
        [$values[$index], $values[$target]] = [$values[$target], $values[$index]];
        return $values;
    }
}
