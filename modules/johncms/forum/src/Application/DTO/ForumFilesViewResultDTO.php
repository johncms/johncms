<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\DTO;

final readonly class ForumFilesViewResultDTO
{
    /**
     * @param array<string, mixed> $viewData
     */
    public function __construct(
        public string $caption,
        public ?string $contextName,
        public ?string $contextUrl,
        public string $template,
        public array $viewData,
    ) {
    }
}
