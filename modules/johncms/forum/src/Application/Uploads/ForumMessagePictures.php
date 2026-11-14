<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Uploads;

use Johncms\EditorUploads\EditorUploadBindException;
use Johncms\EditorUploads\EditorUploads;
use Johncms\Modules\Forum\Domain\Repository\ForumMessageRepositoryInterface;

/**
 * The pictures the text of a post shows, as the registry of the editor uploads holds them.
 *
 * The text is read back from the post rather than taken from the form: a message merged into the
 * previous post of its author shows the pictures of both.
 */
final readonly class ForumMessagePictures
{
    public function __construct(
        private EditorUploads $uploads,
        private ForumMessageRepositoryInterface $messages,
    ) {
    }

    /**
     * Give the post the pictures its text shows. Called inside the transaction that saved it.
     *
     * @throws EditorUploadBindException when they cannot be taken; the save is to be rolled back.
     */
    public function bind(int $messageId, int $userId): void
    {
        $text = $this->messages->findTextLocked($messageId);
        if ($text === null) {
            return;
        }

        $this->uploads->bind(ForumEditorUploads::OWNER_TYPE, $messageId, $userId, $text);
    }

    /**
     * The posts are being deleted for good: their pictures go once the deletion commits.
     *
     * @param list<int> $messageIds
     */
    public function detach(array $messageIds): void
    {
        foreach (array_chunk($messageIds, 500) as $chunk) {
            $this->uploads->detach(ForumEditorUploads::OWNER_TYPE, $chunk);
        }
    }
}
