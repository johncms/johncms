<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Guestbook\Application\Access\GuestbookMode;
use Johncms\Modules\Guestbook\Application\UseCases\ClearGuestbookUseCase;
use Johncms\Modules\Guestbook\Domain\Enums\ClearGuestbookPeriod;
use Johncms\System\Http\Request;
use Johncms\System\Http\Session;
use Johncms\System\View\Render;
use Johncms\Validator\Validator;

final readonly class ClearGuestbookController
{
    public function __construct(
        private ControllerContext $context,
        private Request $request,
        private Render $render,
        private Session $session,
        private GuestbookMode $mode,
        private ClearGuestbookUseCase $clearUseCase,
    ) {
        $this->context->initModule('guestbook');
    }

    public function __invoke(): string
    {
        $baseUrl = '/guestbook/';

        if ($this->request->getMethod() === 'POST') {
            $validator = new Validator(['csrf_token' => $this->request->getPost('csrf_token')], ['csrf_token' => ['Csrf']]);
            if (! $validator->isValid()) {
                $this->session->flash('errors', $validator->getErrors());
                redirect($baseUrl);
            }

            $period = ClearGuestbookPeriod::tryFrom((int) $this->request->getPost('cl', 0, FILTER_VALIDATE_INT))
                ?? ClearGuestbookPeriod::OlderThanWeek;

            $this->clearUseCase->execute($this->mode->isAdminClub(), $period);

            $this->session->flash('message', match ($period) {
                ClearGuestbookPeriod::OlderThanWeek => __('All messages older than 1 week were deleted'),
                ClearGuestbookPeriod::OlderThanDay  => __('All messages older than 1 day were deleted'),
                ClearGuestbookPeriod::All           => __('Full clearing is finished'),
            });
            redirect($baseUrl);
        }

        // Request cleaning options
        return $this->render->render('guestbook::clear');
    }
}
