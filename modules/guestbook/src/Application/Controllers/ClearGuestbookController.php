<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Guestbook\Application\Services\GuestbookService;
use Johncms\System\Http\Request;
use Johncms\System\Http\Session;
use Johncms\System\View\Render;
use Johncms\Validator\Validator;

final readonly class ClearGuestbookController
{
    public function __construct(
        private ControllerContext $context,
        private Request $request,
        private GuestbookService $guestbook,
        private Render $render,
        private Session $session
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
            // We clean the Guest, according to the specified parameters
            $period = $this->request->getPost('cl', 0, FILTER_VALIDATE_INT);
            $message = $this->guestbook->clear($period);
            // Set result message
            $this->session->flash('message', $message);
            redirect($baseUrl);
        }

        // Request cleaning options
        return $this->render->render('guestbook::clear');
    }
}
