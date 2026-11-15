<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\DTO;

/** The topic of a section where the last post was written. */
final readonly class ForumLastTopicDTO
{
    public function __construct(
        public string $name,
        public string $url,
        public string $author,
    ) {
    }
}
