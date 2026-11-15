<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\DTO;

final readonly class BulkDeletePostsResultDTO
{
    public function __construct(
        /** Every post left in sight was chosen, so the topic was hidden instead of them. */
        public bool $topicHidden,
        /** The section of the topic, where to go once the topic is hidden. */
        public string $sectionUrl,
    ) {
    }
}
