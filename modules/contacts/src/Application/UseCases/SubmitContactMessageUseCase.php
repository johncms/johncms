<?php

declare(strict_types=1);

namespace Johncms\Modules\Contacts\Application\UseCases;

use Johncms\Mail\Exception\InvalidEmailAddressException;
use Johncms\Mail\Queue\MailQueueInterface;
use Johncms\Mail\Queue\QueuedEmailDTO;
use Johncms\Modules\Contacts\Application\Services\ContactSettingsProvider;
use Johncms\Modules\Contacts\Domain\Enums\ContactMessageStatus;
use Johncms\Modules\Contacts\Application\DTO\CreateContactMessageDTO;
use Johncms\Modules\Contacts\Domain\Models\ContactMessage;
use Johncms\Modules\Contacts\Domain\Repository\ContactMessageRepositoryInterface;
use Johncms\System\i18n\Translator;
use Psr\Log\LoggerInterface;

final readonly class SubmitContactMessageUseCase
{
    public function __construct(
        private ContactMessageRepositoryInterface $repository,
        private ContactSettingsProvider $settingsProvider,
        private Translator $translator,
        private MailQueueInterface $mailQueue,
        private LoggerInterface $logger,
    ) {
    }

    public function execute(CreateContactMessageDTO $dto): ContactMessage
    {
        $message = $this->repository->create(
            [
                'user_id'    => $dto->userId,
                'name'       => $dto->name,
                'email'      => $dto->email,
                'message'    => $dto->message,
                'status'     => ContactMessageStatus::New,
                'ip_address' => $dto->ip,
                'user_agent' => $dto->userAgent,
            ]
        );

        $this->queueNotification($message);

        return $message;
    }

    /**
     * Puts an admin notification into the mail queue. The queue is processed by the mail cron task,
     * so a slow SMTP server never blocks the request.
     */
    private function queueNotification(ContactMessage $message): void
    {
        $config = config('johncms');
        $notifyEmail = $this->settingsProvider->getSettings()->notifyEmail;
        if ($notifyEmail === '') {
            $notifyEmail = (string) ($config['email'] ?? '');
        }

        if ($notifyEmail === '') {
            return;
        }

        try {
            $this->mailQueue->push(
                new QueuedEmailDTO(
                    template: '@theme/emails/contact-message.twig',
                    locale: $this->translator->getLocale(),
                    recipient: $notifyEmail,
                    recipientName: (string) ($config['copyright'] ?? ''),
                    subject: __('New message from the contact form'),
                    priority: 2,
                    variables: [
                        'sender_name'  => $message->name,
                        'sender_email' => $message->email,
                        'message_text' => $message->message,
                        'admin_link'   => rtrim((string) ($config['homeurl'] ?? ''), '/') . '/admin/contacts/messages/' . $message->id,
                    ],
                    // Answering the notification answers the visitor, not the mailbox of the site.
                    replyTo: $message->email,
                    replyToName: $message->name,
                )
            );
        } catch (InvalidEmailAddressException $exception) {
            // The notification address comes from the settings of the site rather than from a
            // validated form. A typo there is not the problem of the visitor who just wrote in:
            // their message is saved, and the administrator finds the reason in the log.
            $this->logger->error(
                '[Contacts] The notification could not be queued',
                ['notify_email' => $notifyEmail, 'exception' => $exception]
            );
        }
    }
}
