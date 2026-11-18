<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Uploads;

use Johncms\FileInfo;
use Johncms\Storage\StorageException;
use Johncms\Http\UploadedFileDTO;
use Johncms\Modules\Forum\Application\DTO\ForumFileLimitsDTO;
use Johncms\Modules\Forum\Application\DTO\ForumSettingsDTO;
use Johncms\Modules\Forum\Domain\Models\ForumFile;
use Johncms\Modules\Forum\Domain\Repository\ForumFileRepositoryInterface;
use Johncms\Modules\Forum\Domain\Repository\ForumMessageRepositoryInterface;
use Johncms\Modules\Forum\Domain\Storage\ForumAttachmentStorageInterface;
use LogicException;
use Throwable;

/**
 * The files a post is sent with, chosen in the form that writes it: a new topic, a post, an answer
 * or an edit.
 *
 * All or nothing: every file is checked before any is written, and a post one of whose files is
 * refused is not saved at all. The rows are written in the transaction of the post; a file written
 * before a failure the store sees is deleted again. A file left behind by a process that died
 * between the disk and the commit is for the cleanup of orphans.
 */
final readonly class ForumPostFiles
{
    /**
     * The type the listings of files group an attachment under, by the group of its extension in
     * the configuration; 9 is anything else.
     */
    private const array TYPES = [
        'windows'   => 1,
        'java'      => 2,
        'sis'       => 3,
        'documents' => 4,
        'pictures'  => 5,
        'archives'  => 6,
        'video'     => 7,
        'audio'     => 8,
    ];

    private const int OTHER_TYPE = 9;

    public function __construct(
        private ForumAttachmentStorageInterface $attachments,
        private ForumFileRepositoryInterface $files,
        private ForumMessageRepositoryInterface $messages,
    ) {
    }

    public function limits(): ForumFileLimitsDTO
    {
        $extensions = [];
        foreach ((array) config('forum.extensions', []) as $group) {
            foreach ((array) $group as $extension) {
                $extensions[] = strtolower((string) $extension);
            }
        }

        return new ForumFileLimitsDTO(
            maxFiles: ForumSettingsDTO::clampMaxFiles((int) config('forum.settings.max_files_per_post', ForumSettingsDTO::DEFAULT_MAX_FILES)),
            maxSizeKb: (int) config('johncms.flsz', 0),
            extensions: array_values(array_unique($extensions)),
        );
    }

    /** How many files the post holds already, hidden ones included. */
    public function countOf(int $postId): int
    {
        return count($this->files->getByPostId($postId));
    }

    /**
     * What is wrong with the files, as messages for the field; empty when they may be attached.
     *
     * @param list<UploadedFileDTO> $files
     * @return list<string>
     */
    public function check(array $files, int $alreadyAttached = 0): array
    {
        if ($files === []) {
            return [];
        }

        $limits = $this->limits();
        $errors = [];
        if ($alreadyAttached + count($files) > $limits->maxFiles) {
            $errors[] = $alreadyAttached > 0
                ? sprintf(__('A post may hold %d files at most, and this one holds %d already.'), $limits->maxFiles, $alreadyAttached)
                : sprintf(__('A post may hold %d files at most.'), $limits->maxFiles);
        }

        foreach ($files as $file) {
            $error = $this->checkOne($file, $limits);
            if ($error !== null) {
                $errors[] = $error;
            }
        }

        return $errors;
    }

    /**
     * Writes the files and their rows. Called in the transaction of the post, once the post is
     * saved and the files have passed check().
     *
     * @param list<UploadedFileDTO> $files
     * @throws StorageException
     */
    public function store(int $messageId, array $files): void
    {
        if ($files === []) {
            return;
        }

        $message = $this->messages->findById($messageId) ?? throw new LogicException(sprintf('The post %d is not saved.', $messageId));
        $message->loadMissing('topic.section');
        $written = [];
        try {
            foreach ($files as $file) {
                $name = $this->freeName((string) (new FileInfo((string) $file->clientName))->getCleanName(), $written);
                $this->attachments->storeFile($name, $file->tmpPath);
                $written[] = $name;

                $row = new ForumFile();
                $row->cat = $message->topic->section->parent;
                $row->subcat = $message->topic->section_id;
                $row->topic = $message->topic_id;
                $row->post = $message->id;
                $row->time = $message->date;
                $row->filename = $name;
                $row->filetype = $this->typeOf($name);
                $this->files->save($row);
            }
        } catch (Throwable $exception) {
            foreach ($written as $name) {
                $this->attachments->delete($name);
            }

            throw $exception;
        }
    }

    private function checkOne(UploadedFileDTO $file, ForumFileLimitsDTO $limits): ?string
    {
        $name = (string) $file->clientName;
        if (
            in_array($file->error, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
            || ($file->isValid() && $file->size !== null && $file->size > 1024 * $limits->maxSizeKb)
        ) {
            return sprintf(__('The file %s is larger than %d KB.'), $name, $limits->maxSizeKb);
        }

        if ($file->error === UPLOAD_ERR_PARTIAL) {
            return sprintf(__('The file %s was not uploaded completely. Choose it again.'), $name);
        }

        if (! $file->isValid()) {
            return sprintf(__('The file %s could not be uploaded. Choose it again.'), $name);
        }

        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (! in_array($extension, $limits->extensions, true)) {
            return sprintf(__('Files of this type cannot be attached: %s.'), $name);
        }

        return null;
    }

    /**
     * A name no attachment has yet, the files of this post written just now included.
     *
     * @param list<string> $taken
     */
    private function freeName(string $name, array $taken): string
    {
        $candidate = $name;
        for ($attempt = 1; in_array($candidate, $taken, true) || $this->attachments->exists($candidate); $attempt++) {
            $candidate = time() . '_' . $attempt . '_' . $name;
        }

        return $candidate;
    }

    private function typeOf(string $name): int
    {
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        foreach (self::TYPES as $group => $type) {
            if (in_array($extension, array_map(strtolower(...), (array) config('forum.extensions.' . $group, [])), true)) {
                return $type;
            }
        }

        return self::OTHER_TYPE;
    }
}
