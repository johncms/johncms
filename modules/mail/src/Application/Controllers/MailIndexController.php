<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\Controllers;

final readonly class MailIndexController
{
    public function __invoke(): void
    {
        redirect('/mail/incoming');
    }
}
