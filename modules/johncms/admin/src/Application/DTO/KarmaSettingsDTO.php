<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\DTO;

final readonly class KarmaSettingsDTO
{
    public function __construct(
        public int $karmaPoints,
        public int $forumPosts,
        public bool $enabled,
        public bool $forbidAdmin,
    ) {
    }
}
