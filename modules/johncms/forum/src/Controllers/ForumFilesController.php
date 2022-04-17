<?php

declare(strict_types=1);

namespace Johncms\Forum\Controllers;

use Psr\Http\Message\ResponseInterface;

class ForumFilesController extends BaseForumController
{
    public function addFile(int $messageId): string|ResponseInterface
    {
        return 'Add file page ' . $messageId;
    }
}
