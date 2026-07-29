<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Guestbook\Application\Access\GuestbookMode;
use Johncms\Http\Request;

final readonly class SwitchTypeController
{
    public function __construct(
        private ControllerContext $context,
        private GuestbookMode $guestbookMode,
    ) {
        $this->context->initModule('guestbook');
    }

    public function __invoke(Request $request): string
    {
        $this->guestbookMode->switch($request);
        redirect('/guestbook/');
    }
}
