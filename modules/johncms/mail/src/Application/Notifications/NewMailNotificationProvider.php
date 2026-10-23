<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\Notifications;

use Johncms\Auth\CurrentUser;
use Johncms\Modules\Mail\Domain\Repository\MailMessageRepositoryInterface;
use Johncms\Notifications\SystemNotification;
use Johncms\Notifications\SystemNotificationProviderInterface;

/**
 * The letters of the visitor not read yet — the same ones the inbox calls new.
 */
final readonly class NewMailNotificationProvider implements SystemNotificationProviderInterface
{
    public function __construct(
        private CurrentUser $currentUser,
        private MailMessageRepositoryInterface $messages,
    ) {
    }

    public function notifications(): iterable
    {
        $count = $this->messages->countNewInbox($this->currentUser->id());

        if ($count > 0) {
            yield new SystemNotification('new_mail', d__('mail', 'Mail'), '/mail/incoming', $count);
        }
    }
}
