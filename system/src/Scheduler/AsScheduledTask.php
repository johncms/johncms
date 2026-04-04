<?php

declare(strict_types=1);

namespace Johncms\Scheduler;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class AsScheduledTask
{
    /**
     * @param array<string, scalar|array<array-key, scalar|null>|null> $arguments
     */
    public function __construct(
        public string $expression,
        public ?string $timezone = null,
        public array $arguments = [],
        public bool $withoutOverlapping = false,
        public ?string $description = null,
    ) {
    }
}
