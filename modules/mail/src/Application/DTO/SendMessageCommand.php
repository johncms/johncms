<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\DTO;

use Psr\Http\Message\UploadedFileInterface;

final readonly class SendMessageCommand
{
    public function __construct(
        public int $recipientId,
        public string $text,
        public ?UploadedFileInterface $file = null,
    ) {
    }
}
