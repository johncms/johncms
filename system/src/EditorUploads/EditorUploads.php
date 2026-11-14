<?php

declare(strict_types=1);

namespace Johncms\EditorUploads;

use DateTimeImmutable;
use Johncms\Database\TransactionManagerInterface;
use Johncms\Files\FileStore;
use Johncms\Files\FileStoreException;
use Johncms\Files\StoredFileDTO;
use Johncms\Http\UploadedFileDTO;
use Johncms\Image\EditorImageUploadException;
use Johncms\Image\StoreEditorImageUseCase;
use Throwable;

/**
 * The pictures of the text editor, from the upload to the record that shows them.
 *
 * A picture is uploaded before the form is sent, so for a while it belongs to nobody but the one
 * who uploaded it. The record takes it when it is saved — only the pictures its text actually
 * shows, only the uploader's own or those it already held — and gives it up when it is edited
 * without it or deleted. Whatever is never taken is removed by the cleanup a day later.
 *
 * The browser has no say in any of it: the list of ids the editor posts is not read. The text is
 * what decides, and the server finds the candidates itself.
 */
final readonly class EditorUploads
{
    /** Pictures one user may have uploaded and not sent yet. */
    public const int MAX_UNBOUND_PER_USER = 20;

    /** Pictures one record may show. */
    public const int MAX_PER_OWNER = 10;

    public function __construct(
        private EditorUploadConsumers $consumers,
        private EditorUploadRepositoryInterface $uploads,
        private StoreEditorImageUseCase $storeEditorImage,
        private FileStore $files,
        private TransactionManagerInterface $transactions,
        private ImageSources $imageSources,
    ) {
    }

    /**
     * Store a picture for a record of the given type that the user is writing.
     *
     * The place is reserved first, under a lock of the account, so that uploads sent at the same
     * moment cannot pass the limit together; the file itself is written outside any transaction,
     * as FileStore requires.
     *
     * @throws EditorImageUploadException when the user may not store this picture.
     * @throws FileStoreException when the file could not be stored.
     */
    public function store(UploadedFileDTO $upload, string $ownerType, int $userId): StoredFileDTO
    {
        $directory = $this->consumers->get($ownerType)->directory();

        $reservation = $this->transactions->run(function () use ($userId, $directory): ?int {
            $this->uploads->lockUser($userId);
            if ($this->uploads->countUnbound($userId) >= self::MAX_UNBOUND_PER_USER) {
                return null;
            }

            return $this->uploads->reserve($userId, $directory, new DateTimeImmutable());
        });

        if ($reservation === null) {
            throw new EditorImageUploadException(
                d__('system', 'Too many uploaded pictures are waiting to be sent. Send the text you uploaded them for, or try again tomorrow.')
            );
        }

        try {
            $file = $this->storeEditorImage->execute($upload, $directory);
        } catch (Throwable $exception) {
            $this->uploads->delete($reservation);

            throw $exception;
        }

        try {
            $this->uploads->fillReservation($reservation, $file->id);
        } catch (Throwable $exception) {
            // A file no row knows would only be found by the cleanup a day later.
            $this->files->delete($file->id);

            throw $exception;
        }

        return $file;
    }

    /**
     * Give the record the pictures its text shows. Called inside the transaction that saves the
     * record: a failure rolls the save back.
     *
     * The candidates are the pictures the record already holds and the ones the user uploaded and
     * has not sent yet. A picture of the text that is neither — somebody else's upload, a picture
     * of another record, an outside address — is shown as it is and never taken. A picture the
     * record held and its text no longer shows is removed once the save commits.
     *
     * @throws EditorUploadBindException
     */
    public function bind(string $ownerType, int $ownerId, int $userId, string $html): void
    {
        $owned = $this->uploads->getOwnedFileIds($ownerType, $ownerId);
        $unbound = $this->uploads->getUnboundFileIds($userId);
        $shown = array_flip($this->imageSources->in($html));

        $kept = [];
        foreach ($this->files->getByIds(array_values(array_unique([...$owned, ...$unbound]))) as $file) {
            $key = $this->imageSources->key($file->url);
            if ($key !== null && isset($shown[$key])) {
                $kept[] = $file->id;
            }
        }

        if (count($kept) > self::MAX_PER_OWNER) {
            throw new EditorUploadBindException(
                sprintf(d__('system', 'A text can show at most %d uploaded pictures.'), self::MAX_PER_OWNER)
            );
        }

        $new = array_values(array_diff($kept, $owned));
        if ($new !== [] && $this->uploads->bind($new, $ownerType, $ownerId, $userId) !== count($new)) {
            // Taken by another form or removed by the cleanup between the read and the update.
            throw new EditorUploadBindException(d__('system', 'One of the pictures is no longer available. Upload it again.'));
        }

        $removed = array_values(array_diff($owned, $kept));
        if ($removed !== []) {
            $this->uploads->releaseFiles($ownerType, $ownerId, $removed);
            $this->deleteAfterCommit($removed);
        }
    }

    /**
     * The records are being deleted: their pictures go once the deletion commits, and stay if it
     * is rolled back.
     *
     * @param list<int> $ownerIds
     */
    public function detach(string $ownerType, array $ownerIds): void
    {
        $this->deleteAfterCommit($this->uploads->releaseOwners($ownerType, $ownerIds));
    }

    /** The account is being deleted: the pictures it uploaded and never sent go with it. */
    public function detachUser(int $userId): void
    {
        $this->deleteAfterCommit($this->uploads->releaseUnboundOfUser($userId));
    }

    /**
     * @param list<int> $fileIds
     */
    private function deleteAfterCommit(array $fileIds): void
    {
        if ($fileIds === []) {
            return;
        }

        $this->transactions->afterCommit(fn() => $this->files->deleteMany($fileIds));
    }
}
