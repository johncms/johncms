<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\DTO;

/**
 * The mail settings an administrator can change from the admin panel.
 *
 * Signing and the behaviour of the queue are deliberately not here: they are set once, in
 * mail.local.php, and are not what somebody opens this page for.
 */
final readonly class MailSettingsDTO
{
    public function __construct(
        public string $dsn = '',
        public string $transport = 'sendmail',
        public string $host = '',
        public int $port = 0,
        public string $username = '',
        public string $password = '',
        public string $encryption = '',
        public string $sendmailCommand = '',
        public string $redirectTo = '',
    ) {
    }
}
