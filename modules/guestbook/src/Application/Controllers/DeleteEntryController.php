<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\Controllers;

use Johncms\Modules\Guestbook\Application\Exceptions\GuestbookAccessDeniedException;
use Johncms\Modules\Guestbook\Application\Exceptions\GuestbookEntryNotFoundException;
use Johncms\Modules\Guestbook\Application\UseCases\DeleteGuestbookEntryUseCase;
use Johncms\Modules\Guestbook\Application\UseCases\EnsureGuestbookEntryManageAccessUseCase;
use Johncms\Modules\Guestbook\Application\UseCases\GetGuestbookEntryContextUseCase;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\Http\Session;
use Symfony\Component\HttpFoundation\Response;

final readonly class DeleteEntryController
{
    public function __construct(
        private Session $session,
        private GetGuestbookEntryContextUseCase $contextUseCase,
        private EnsureGuestbookEntryManageAccessUseCase $manageAccessUseCase,
        private DeleteGuestbookEntryUseCase $deleteUseCase,
    ) {
    }

    public function __invoke(Request $request): ViewResponse
    {
        $baseUrl = '/guestbook/';

        if ($request->getMethod() !== 'POST') {
            $id = $request->queryInt('id');
            return new ViewResponse('@guestbook/public/confirm-delete.twig', ['id' => $id]);
        }

        $id = $request->bodyInt('id');

        try {
            $entry = $this->contextUseCase->execute($id);
            $this->manageAccessUseCase->execute($entry);
        } catch (GuestbookEntryNotFoundException) {
            return new ViewResponse(
                '@theme/pages/result.twig',
                [
                    'title'    => __('Delete message'),
                    'message'  => __('Wrong data'),
                    'type'     => 'alert-danger',
                    'back_url' => $baseUrl,
                ]
            );
        } catch (GuestbookAccessDeniedException) {
            return new ViewResponse(
                '@theme/pages/result.twig',
                [
                    'title'    => __('Delete message'),
                    'message'  => __('Wrong data'),
                    'type'     => 'alert-danger',
                    'back_url' => $baseUrl,
                ],
                Response::HTTP_FORBIDDEN
            );
        }

        $this->deleteUseCase->execute($entry);
        $this->session->flash('message', __('The message was deleted'));
        redirect($baseUrl);
    }
}
