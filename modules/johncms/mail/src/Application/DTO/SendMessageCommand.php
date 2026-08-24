<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\DTO;

use Johncms\Http\UploadedFileDTO;

final readonly class SendMessageCommand
{
    public function __construct(
        public int $recipientId,
        public string $text,
        public ?UploadedFileDTO $file = null,
    ) {
    }
}
