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
        /**
         * Whether the maintenance screen offers a button for it. A command that needs an argument
         * has nothing to offer there — it is queued from wherever that argument comes from.
         */
        public bool $listed = true,
    ) {
    }
}
