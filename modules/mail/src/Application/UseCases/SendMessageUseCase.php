<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\UseCases;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Http\UploadedFileDTO;
use Johncms\Modules\Mail\Application\DTO\SendMessageCommand;
use Johncms\Modules\Mail\Application\Exceptions\SendMessageException;
use Johncms\Modules\Mail\Application\Services\MailFileService;
use Johncms\Modules\Mail\Domain\Models\MailMessage;
use Johncms\Modules\Mail\Domain\Repository\ContactRepositoryInterface;
use Johncms\Modules\Mail\Domain\Repository\MailMessageRepositoryInterface;
use Johncms\Security\AntifloodCheckerInterface;
use Johncms\Users\User;

final readonly class SendMessageUseCase
{
    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private MailMessageRepositoryInterface $mailMessageRepository,
        private ContactRepositoryInterface $contactRepository,
        private MailFileService $mailFileService,
        private AntifloodCheckerInterface $antifloodChecker,
        private User $currentUser,
    ) {
    }

    public function execute(SendMessageCommand $command): void
    {
        $me = $this->currentUser->id;
        $recipientId = $command->recipientId;

        if (! empty($this->currentUser->ban['1']) || ! empty($this->currentUser->ban['3'])) {
            throw new SendMessageException(__('Access forbidden'));
        }

        $recipient = User::query()->find($recipientId);
        if ($recipient === null) {
            throw new SendMessageException(__('User does not exists'));
        }

        $text = trim($command->text);
        if ($text === '') {
            throw new SendMessageException(__('Message cannot be empty'));
        }

        if ($recipientId === $me) {
            throw new SendMessageException(__('You cannot send messages to yourself'));
        }

        $flood = $this->antifloodChecker->getRemainingSeconds();
        if ($flood) {
            throw new SendMessageException(
                sprintf(__('You cannot add the message so often. Please, wait %d sec.'), $flood)
            );
        }

        $this->ensureRecipientAllowsMessages($recipient);

        if ($this->contactRepository->isBlocked($me, $recipientId)) {
            throw new SendMessageException(__('The user at your ignore list. Sending the message is impossible.'));
        }
        if ($this->contactRepository->isBlocked($recipientId, $me)) {
            throw new SendMessageException(__('The user added you in the ignore list. Sending the message isn\'t possible.'));
        }

        $fileName = '';
        $fileSize = 0;
        if ($command->file !== null) {
            [$fileName, $fileSize] = $this->validateFile($command->file);
        }

        $lastMessage = $this->mailMessageRepository->getLastMessageBetween($me, $recipientId);
        if ($lastMessage !== null && $lastMessage->text === $text) {
            throw new SendMessageException(__('Message already exists'));
        }

        // Ensure contact records exist in both directions (legacy keeps mutual contacts).
        $contactExisted = $this->contactRepository->findContact($me, $recipientId) !== null;
        $reverseExisted = $this->contactRepository->findContact($recipientId, $me) !== null;
        $this->contactRepository->addContact($me, $recipientId);
        $this->contactRepository->addContact($recipientId, $me);

        if ($command->file !== null && ! $this->mailFileService->storeUploadedFile($command->file, $fileName)) {
            throw new SendMessageException(__('Error uploading file'));
        }

        $now = time();

        $message = new MailMessage([
            'user_id'   => $me,
            'from_id'   => $recipientId,
            'text'      => $text,
            'time'      => $now,
            'file_name' => $fileName,
            'size'      => $fileSize,
        ]);
        $this->mailMessageRepository->save($message);

        $this->currentUser->update(['lastpost' => $now]);

        // Refresh contact activity time only when both records already existed
        // (newly created contacts were just stored with the current time).
        if ($contactExisted && $reverseExisted) {
            $this->contactRepository->updateContactTime($me, $recipientId, $now);
            $this->contactRepository->updateContactTime($recipientId, $me, $now);
        }
    }

    private function ensureRecipientAllowsMessages(User $recipient): void
    {
        if ($this->accessChecker->allows(CorePermissions::SMILIES_ADMIN_USE)) {
            return;
        }

        $setMail = is_array($recipient->set_mail) ? $recipient->set_mail : [];
        $access = (int) ($setMail['access'] ?? 0);
        if ($access === 0) {
            return;
        }

        $contact = $this->contactRepository->findContact($recipient->id, $this->currentUser->id);

        if ($access === 1 && $contact === null) {
            throw new SendMessageException(__('To this user can write only contacts'));
        }

        if ($access === 2 && ($contact === null || ! $contact->friends)) {
            throw new SendMessageException(__('To this user can write only friends'));
        }
    }

    /**
     * @return array{0: string, 1: int} Stored file name and size
     */
    private function validateFile(UploadedFileDTO $file): array
    {
        if (! $file->isValid()) {
            throw new SendMessageException(__('Error uploading file'));
        }

        $parsed = $this->mailFileService->parseFileName((string) $file->clientName);

        if ($parsed['filename'] === '') {
            throw new SendMessageException(__('It is forbidden to upload files without a name'));
        }

        if ($parsed['fileext'] === '') {
            throw new SendMessageException(__('It is forbidden to upload files without extension'));
        }

        $size = (int) $file->size;
        $maxKb = (int) (config('johncms')['flsz'] ?? 0);
        if ($size > 1024 * $maxKb) {
            throw new SendMessageException(__('The size of the file exceeds the maximum allowable upload'));
        }

        if (preg_match('/[^a-z0-9.()+_-]/', $parsed['filename'])) {
            throw new SendMessageException(__('File name contains invalid characters'));
        }

        if (! $this->mailFileService->isAllowedExtension($parsed['fileext'])) {
            throw new SendMessageException(
                __('Forbidden file type! By uploading permitted only files with the following extension')
                . ': ' . implode(', ', MailFileService::ALLOWED_EXTENSIONS)
            );
        }

        $fileName = $this->mailFileService->uniqueFileName($parsed['filename'] . '.' . $parsed['fileext']);

        return [$fileName, $size];
    }
}
