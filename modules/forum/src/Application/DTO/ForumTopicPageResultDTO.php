<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\DTO;

final readonly class ForumTopicPageResultDTO
{
    /**
     * @param array<string, mixed> $viewData
     */
    public function __construct(
        public array $viewData,
        public string $canonical,
        public string $title,
    ) {
    }
}
