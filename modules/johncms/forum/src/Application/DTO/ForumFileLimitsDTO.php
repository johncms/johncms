<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\DTO;

/**
 * What the file field of a form that writes a post accepts, as the form tells it beforehand.
 */
final readonly class ForumFileLimitsDTO
{
    /**
     * @param int          $maxFiles   How many files one post may hold, those already attached counted in.
     * @param int          $maxSizeKb  The largest file, in kilobytes.
     * @param list<string> $extensions The types allowed, lower case, without the dot.
     */
    public function __construct(
        public int $maxFiles,
        public int $maxSizeKb,
        public array $extensions,
    ) {
    }

    /** The value of the accept attribute of the field. */
    public function accept(): string
    {
        return implode(',', array_map(static fn (string $extension): string => '.' . $extension, $this->extensions));
    }
}
