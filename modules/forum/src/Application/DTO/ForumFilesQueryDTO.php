<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\DTO;

final readonly class ForumFilesQueryDTO
{
    public function __construct(
        public int $start,
        public int $contextCategoryId,
        public int $contextSectionId,
        public int $contextTopicId,
        public int $fileType,
        public bool $isNew,
    ) {
    }
}
