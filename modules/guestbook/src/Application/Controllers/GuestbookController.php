<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\Controllers;

use Johncms\Exceptions\ValidationException;
use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Guestbook\Application\Access\GuestbookAccess;
use Johncms\Modules\Guestbook\Application\Forms\GuestbookForm;
use Johncms\Modules\Guestbook\Application\Services\GuestbookService;
use Johncms\Modules\Guestbook\Application\UseCases\ListGuestbookEntriesUseCase;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\Http\Session;
use Johncms\System\View\Render;

final readonly class GuestbookController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private Request $request,
        private Session $session,
        private GuestbookService $guestbook,
        private GuestbookAccess $access,
        private ListGuestbookEntriesUseCase $guestbookEntries,
        private GuestbookForm $form,
    ) {
        $this->controllerContext->initModule('guestbook');
    }

    public function __invoke(): string
    {
        $pageTitle = $this->guestbook->isGuestbook() ? __('Guestbook') : __('Admin Club');
        $page = max(1, (int) $this->request->getQuery('page', 1));
        $title = $pageTitle;
        if ($page > 1) {
            $title .= ' - ' . d__('system', 'Page') . ' ' . $page;
        }
        $baseUrl = '/guestbook/';
        $this->navChain->add($pageTitle, $baseUrl);

        if ($this->access->isClosed() && ! $this->access->canClear()) {
            echo $this->render->render(
                'system::pages/result',
                [
                    'title'    => $pageTitle,
                    'message'  => __('Guestbook is closed'),
                    'type'     => 'alert-danger',
                    'back_url' => '/',
                ]
            );
            exit;
        }

        $this->render->addData([
            'title'       => $title,
            'page_title'  => $pageTitle,
            'description' => $title,
        ]);

        $flash_errors = $this->session->getFlash('errors');
        $errors = $flash_errors ?? [];

        // If the form was sent using POST method, then try to create the new post.
        if ($this->request->getMethod() === 'POST' && $this->access->canWrite()) {
            try {
                $this->guestbook->create();
                $this->session->flash('message', __('Your message was added successfully'));
                redirect($baseUrl);
            } catch (ValidationException $exception) {
                $errors = $exception->getErrors();
            }
        }

        $posts = $this->guestbookEntries->execute();

        return $this->render->render(
            'guestbook::index',
            [
                'posts'      => $posts['posts'],
                'pagination' => $posts['pagination'],
                'isClosed'   => $this->access->isClosed(),
                'canWrite'   => $this->access->canWrite(),
                'canClear'   => $this->access->canClear(),
                'errors'     => $errors,
                'formData'   => $this->form->getFormData(),
                'captcha'    => $this->guestbook->getCaptcha(),
                'message'    => $this->session->getFlash('message'),
            ]
        );
    }
}
