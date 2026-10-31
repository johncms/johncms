<?php

declare(strict_types=1);

namespace Johncms\Checker\DTO;

use InvalidArgumentException;
use Johncms\Checker\CheckSeverity;
use Johncms\Checker\CheckStatus;

final readonly class CheckResultDTO
{
    public function __construct(
        public string $id,
        public string $name,
        public string $value,
        public CheckStatus $status,
        public CheckSeverity $severity,
        public string $description,
        public ?string $documentationUrl = null,
    ) {
        if ($documentationUrl !== null && ! preg_match('~^https?://~i', $documentationUrl)) {
            throw new InvalidArgumentException('A system check documentation URL must use HTTP or HTTPS.');
        }
    }
}
