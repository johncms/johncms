<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\DTO;

use Johncms\Modules\Forum\Domain\Models\ForumMessage;
use Johncms\Modules\Forum\Domain\Models\ForumSection;
use Johncms\Modules\Forum\Domain\Models\ForumTopic;

final readonly class EditPostContextDTO
{
    public function __construct(
        public ForumMessage $message,
        public ForumTopic $topic,
        public ForumSection $section,
        public bool $canModerate,
        public int $page,
        public int $posts,
        public string $backUrl,
    ) {
    }
}
