<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\DTO;

final readonly class ModulesAccessDTO
{
    public function __construct(
        public int $registration,
        public bool $libraryComments,
        public bool $downloadsComments,
        public int $community,
    ) {
    }
}
