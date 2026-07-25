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
        private Request $request,
        private GuestbookMode $guestbookMode,
    ) {
        $this->context->initModule('guestbook');
    }

    public function __invoke(): string
    {
        $this->guestbookMode->switch($this->request);
        redirect('/guestbook/');
    }
}
