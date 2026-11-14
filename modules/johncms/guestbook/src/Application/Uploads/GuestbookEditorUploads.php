<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\Uploads;

use Johncms\EditorUploads\EditorUploadConsumerInterface;

/** The pictures of the guestbook entries, the reply of the staff included, are tracked by EditorUploads. */
final readonly class GuestbookEditorUploads implements EditorUploadConsumerInterface
{
    public const string OWNER_TYPE = 'guestbook';

    public function ownerType(): string
    {
        return self::OWNER_TYPE;
    }

    public function directory(): string
    {
        return 'guestbook';
    }
}
