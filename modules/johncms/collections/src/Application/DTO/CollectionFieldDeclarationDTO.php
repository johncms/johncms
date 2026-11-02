<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\DTO;

use Johncms\Modules\Collections\Domain\Enums\FieldType;

final readonly class CollectionFieldDeclarationDTO
{
    /** @param array<string, mixed> $settings */
    public function __construct(
        public string $code,
        public CollectionLabelDTO $label,
        public FieldType $type,
        public bool $multiple = false,
        public bool $required = false,
        public ?string $role = null,
        public bool $inList = false,
        public int $sort = 100,
        public array $settings = [],
        public ?string $renamedFrom = null,
    ) {
        foreach ([$code, ...($renamedFrom !== null ? [$renamedFrom] : [])] as $value) {
            if (! preg_match('/^[a-z0-9_-]{1,255}$/D', $value)) {
                throw new \InvalidArgumentException('Invalid declared field code.');
            }
        }
        if ($role !== null && ! preg_match('/^[a-z0-9_]{1,255}$/D', $role)) {
            throw new \InvalidArgumentException('Invalid field role.');
        }
        if ($sort < -2147483648 || $sort > 2147483647) {
            throw new \InvalidArgumentException('Declared field sorting must fit a signed 32-bit integer.');
        }
        json_encode($settings, JSON_THROW_ON_ERROR);
    }
}
