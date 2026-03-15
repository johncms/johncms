<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\DTO;

use Johncms\Modules\Forum\Domain\Models\ForumSection;

final readonly class NewTopicContextDTO
{
    public function __construct(
        public ForumSection $section,
    ) {
    }
}
