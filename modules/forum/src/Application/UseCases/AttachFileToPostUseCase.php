<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use GuzzleHttp\Psr7\UploadedFile;
use Illuminate\Support\Collection;
use Johncms\FileInfo;
use Johncms\Modules\Forum\Application\DTO\AttachFileToPostResultDTO;
use Johncms\Modules\Forum\Application\Exceptions\UploadException;
use Johncms\Modules\Forum\Domain\Models\ForumFile;
use Johncms\Modules\Forum\Domain\Models\ForumMessage;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Domain\Repository\ForumFileRepositoryInterface;
use Johncms\Modules\Forum\Domain\Repository\ForumMessageRepositoryInterface;
use Johncms\Users\User;

final readonly class AttachFileToPostUseCase
{
    public function __construct(
        private ForumMessageRepositoryInterface $messageRepository,
        private ForumFileRepositoryInterface $fileRepository,
        private User $currentUser,
    ) {
    }

    /**
     * @param array<string, UploadedFile|array> $uploadedFiles
     * @param array<string, array<int, string>> $extensions
     */
    public function execute(
        int $messageId,
        array $extensions,
        int $maxFileSizeKb,
        array $uploadedFiles,
    ): AttachFileToPostResultDTO {
        $message = $this->messageRepository->findById($messageId);
        if ($message === null) {
            throw new ForumNotFoundException(sprintf('Message with id "%s" could not be found.', $messageId));
        }

        $topicId = (int) $message->topic_id;

        if (empty($uploadedFiles) || empty($uploadedFiles['fail']) || ! $uploadedFiles['fail'] instanceof UploadedFile) {
            throw new UploadException([__('Error uploading file')]);
        }

        $file = $uploadedFiles['fail'];
        $fileInfo = new FileInfo($file->getClientFilename());
        $extension = strtolower($fileInfo->getExtension());
        $errors = [];

        if ($file->getSize() !== null && $file->getSize() > 1024 * $maxFileSizeKb) {
            $errors[] = __('File size exceed') . ' ' . $maxFileSizeKb . 'kb.';
        }

        $extensionsCollection = new Collection($extensions);
        $allExtensions = $extensionsCollection->flatten();
        if ($allExtensions->search($extension, true) === false) {
            $errors[] = __('The forbidden file format.<br>You can upload files of the following extension') . ':<br>' . $allExtensions->implode(', ');
        }

        $fileName = $fileInfo->getCleanName();
        if (file_exists(UPLOAD_PATH . 'forum/attach/' . $fileName)) {
            $fileName = time() . $fileName;
        }

        if (! $errors) {
            $file->moveTo(UPLOAD_PATH . 'forum/attach/' . $fileName);
            if (! $file->isMoved()) {
                $errors[] = __('Error uploading file');
            }
        }

        if ($errors) {
            throw new UploadException($errors);
        }

        $message->loadMissing(['topic.section']);
        $fileType = $this->resolveFileType($extension, $extensionsCollection);
        $forumFile = new ForumFile();
        $forumFile->cat = $message->topic->section->parent;
        $forumFile->subcat = $message->topic->section_id;
        $forumFile->topic = $message->topic_id;
        $forumFile->post = $message->id;
        $forumFile->time = $message->date;
        $forumFile->filename = $fileName;
        $forumFile->filetype = $fileType;
        $this->fileRepository->save($forumFile);

        $page = $this->getMessagePage((int) $message->topic_id);

        return new AttachFileToPostResultDTO(
            fileAttached: true,
            topicId: $topicId,
            page: $page
        );
    }

    private function resolveFileType(string $extension, Collection $extensions): int
    {
        if (in_array($extension, $extensions->get('windows', []), true)) {
            return 1;
        }

        if (in_array($extension, $extensions->get('java', []), true)) {
            return 2;
        }

        if (in_array($extension, $extensions->get('sis', []), true)) {
            return 3;
        }

        if (in_array($extension, $extensions->get('documents', []), true)) {
            return 4;
        }

        if (in_array($extension, $extensions->get('pictures', []), true)) {
            return 5;
        }

        if (in_array($extension, $extensions->get('archives', []), true)) {
            return 6;
        }

        if (in_array($extension, $extensions->get('video', []), true)) {
            return 7;
        }

        if (in_array($extension, $extensions->get('audio', []), true)) {
            return 8;
        }

        return 9;
    }

    private function getMessagePage(int $topicId): int
    {
        $total = (int) ForumMessage::query()->where('topic_id', $topicId)->count();
        $perPage = (int) ($this->currentUser->set_user->kmess ?? $this->currentUser->config->kmess ?? 10);
        if ($perPage <= 0) {
            $perPage = 10;
        }

        return max(1, (int) ceil($total / $perPage));
    }
}
