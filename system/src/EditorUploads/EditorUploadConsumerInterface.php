<?php

declare(strict_types=1);

namespace Johncms\EditorUploads;

/**
 * A kind of record whose text editor uploads pictures through EditorUploads.
 *
 * Implementing it is what switches the tracking on for the record: its uploads are registered,
 * counted against the limit of the uploader, and cleaned up when they are abandoned. A module
 * implements it in the same change that moves its existing pictures into the registry — the
 * cleanup removes every file of the directory that the registry does not know.
 */
interface EditorUploadConsumerInterface
{
    /** Key of the record the pictures belong to, e.g. `comment`. At most 32 characters. */
    public function ownerType(): string;

    /** Directory of the disk the pictures are stored under, e.g. `comments`. Owned by this consumer alone. */
    public function directory(): string;
}
