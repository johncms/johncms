<?php

declare(strict_types=1);

namespace Johncms\Comments;

use Johncms\EditorUploads\EditorUploadConsumerInterface;

/** The pictures of the comments are tracked by EditorUploads. */
final readonly class CommentEditorUploads implements EditorUploadConsumerInterface
{
    public const string OWNER_TYPE = 'comment';

    public function ownerType(): string
    {
        return self::OWNER_TYPE;
    }

    public function directory(): string
    {
        return 'comments';
    }
}
