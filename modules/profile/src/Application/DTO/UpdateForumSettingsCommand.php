<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\DTO;

final readonly class UpdateForumSettingsCommand
{
    public function __construct(
        public bool $farea,
        public bool $upfp,
        public bool $preview,
        public int $postclip,
    ) {
    }
}
