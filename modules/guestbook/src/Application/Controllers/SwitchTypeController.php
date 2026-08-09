<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\Controllers;

use Johncms\Modules\Guestbook\Application\Access\GuestbookMode;
use Johncms\Http\Request;

final readonly class SwitchTypeController
{
    public function __construct(
        private GuestbookMode $guestbookMode,
    ) {
    }

    public function __invoke(Request $request): string
    {
        $this->guestbookMode->switch($request);
        redirect('/guestbook/');
    }
}
