<?php

declare(strict_types=1);

namespace Johncms\Modules\Contacts\Application\UseCases;

use Johncms\Mail\EmailMessage;
use Johncms\Modules\Contacts\Application\Services\ContactSettingsProvider;
use Johncms\Modules\Contacts\Domain\Enums\ContactMessageStatus;
use Johncms\Modules\Contacts\Application\DTO\CreateContactMessageDTO;
use Johncms\Modules\Contacts\Domain\Models\ContactMessage;
use Johncms\Modules\Contacts\Domain\Repository\ContactMessageRepositoryInterface;
use Johncms\System\i18n\Translator;

final readonly class SubmitContactMessageUseCase
{
    public function __construct(
        private ContactMessageRepositoryInterface $repository,
        private ContactSettingsProvider $settingsProvider,
        private Translator $translator,
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

        (new EmailMessage())->create(
            [
                'priority' => 2,
                'locale'   => $this->translator->getLocale(),
                'template' => '@theme/emails/contact-message.twig',
                'fields'   => [
                    'email_to'     => $notifyEmail,
                    'name_to'      => (string) ($config['copyright'] ?? ''),
                    'subject'      => __('New message from the contact form'),
                    'sender_name'  => $message->name,
                    'sender_email' => $message->email,
                    'message_text' => $message->message,
                    'admin_link'   => rtrim((string) ($config['homeurl'] ?? ''), '/') . '/admin/contacts/messages/' . $message->id,
                ],
            ]
        );
    }
}
