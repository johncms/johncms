<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Uploads;

use Johncms\EditorUploads\EditorUploadConsumerInterface;

/** The pictures of the text of the forum posts are tracked by EditorUploads. */
final readonly class ForumEditorUploads implements EditorUploadConsumerInterface
{
    public const string OWNER_TYPE = 'forum_message';

    public function ownerType(): string
    {
        return self::OWNER_TYPE;
    }

    public function directory(): string
    {
        return 'forum_files';
    }
}
