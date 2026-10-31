<?php

declare(strict_types=1);

namespace Johncms\Checker\DTO;

use Johncms\Checker\CheckProfile;

final readonly class CheckDefinitionDTO
{
    /** @param list<CheckProfile> $profiles */
    public function __construct(
        public string $id,
        public string $name,
        public string $group,
        public string $icon = 'gauge',
        public array $profiles = [CheckProfile::Page],
        public int $order = 100,
    ) {
    }
}
