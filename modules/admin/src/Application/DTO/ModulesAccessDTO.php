<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\DTO;

final readonly class ModulesAccessDTO
{
    public function __construct(
        public int $registration,
        public int $guestbook,
        public int $library,
        public bool $libraryComments,
        public int $downloads,
        public bool $downloadsComments,
        public int $community,
    ) {
    }
}
