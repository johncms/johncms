<?php

declare(strict_types=1);

namespace Johncms\AdminTasks;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class AsAdminTask
{
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public bool $background = false,
    ) {
    }
}
