<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Guestbook\Application\Exceptions\GuestbookAccessDeniedException;
use Johncms\Modules\Guestbook\Application\Exceptions\GuestbookEntryNotFoundException;
use Johncms\Modules\Guestbook\Application\UseCases\DeleteGuestbookEntryUseCase;
use Johncms\Modules\Guestbook\Application\UseCases\EnsureGuestbookEntryManageAccessUseCase;
use Johncms\Modules\Guestbook\Application\UseCases\GetGuestbookEntryContextUseCase;
use Johncms\System\Http\Request;
use Johncms\System\Http\Session;
use Johncms\System\View\Render;
use Johncms\Validator\Validator;

final readonly class DeleteEntryController
{
    public function __construct(
        private ControllerContext $context,
        private Request $request,
        private Render $render,
        private Session $session,
        private GetGuestbookEntryContextUseCase $contextUseCase,
        private EnsureGuestbookEntryManageAccessUseCase $manageAccessUseCase,
        private DeleteGuestbookEntryUseCase $deleteUseCase,
    ) {
        $this->context->initModule('guestbook');
    }

    public function __invoke(): string
    {
        $baseUrl = '/guestbook/';

        if ($this->request->getMethod() !== 'POST') {
            $id = (int) $this->request->getQuery('id', 0, FILTER_VALIDATE_INT);
            return $this->render->render('guestbook::confirm_delete', ['id' => $id]);
        }

        $validator = new Validator(['csrf_token' => $this->request->getPost('csrf_token')], ['csrf_token' => ['Csrf']]);
        if (! $validator->isValid()) {
            $this->session->flash('errors', $validator->getErrors());
            redirect($baseUrl);
        }

        $id = (int) $this->request->getPost('id', 0, FILTER_VALIDATE_INT);

        try {
            $entry = $this->contextUseCase->execute($id);
            $this->manageAccessUseCase->execute($entry);
        } catch (GuestbookEntryNotFoundException) {
            return $this->render->render(
                'system::pages/result',
                [
                    'title'    => __('Delete message'),
                    'message'  => __('Wrong data'),
                    'type'     => 'alert-danger',
                    'back_url' => $baseUrl,
                ]
            );
        } catch (GuestbookAccessDeniedException) {
            http_response_code(403);
            return $this->render->render(
                'system::pages/result',
                [
                    'title'    => __('Delete message'),
                    'message'  => __('Wrong data'),
                    'type'     => 'alert-danger',
                    'back_url' => $baseUrl,
                ]
            );
        }

        $this->deleteUseCase->execute($entry);
        $this->session->flash('message', __('The message was deleted'));
        redirect($baseUrl);
    }
}
