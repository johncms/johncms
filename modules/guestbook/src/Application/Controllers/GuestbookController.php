<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Guestbook\Application\Access\GuestbookAccess;
use Johncms\Modules\Guestbook\Application\Access\GuestbookMode;
use Johncms\Modules\Guestbook\Application\DTO\CreateGuestbookEntryDTO;
use Johncms\Modules\Guestbook\Application\Forms\GuestbookForm;
use Johncms\Modules\Guestbook\Application\Services\GuestbookCaptchaService;
use Johncms\Modules\Guestbook\Application\UseCases\CreateGuestbookEntryUseCase;
use Johncms\Modules\Guestbook\Application\UseCases\ListGuestbookEntriesUseCase;
use Johncms\NavChain;
use Johncms\System\Http\Environment;
use Johncms\System\Http\Request;
use Johncms\System\Http\Session;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Johncms\Validator\Validator;

final readonly class GuestbookController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private Request $request,
        private Session $session,
        private Environment $environment,
        private User $user,
        private GuestbookAccess $access,
        private GuestbookMode $mode,
        private ListGuestbookEntriesUseCase $guestbookEntries,
        private CreateGuestbookEntryUseCase $createEntry,
        private GuestbookCaptchaService $captchaService,
        private GuestbookForm $form,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
        $this->controllerContext->initModule('guestbook');
    }

    public function __invoke(): string
    {
        $pageTitle = $this->mode->isGuestbook() ? __('Guestbook') : __('Admin Club');
        $baseUrl = '/guestbook/';
        $this->navChain->add($pageTitle, $baseUrl);

        if ($this->access->isClosed() && ! $this->access->canClear()) {
            return $this->render->render(
                'system::pages/result',
                [
                    'title'    => $pageTitle,
                    'message'  => __('Guestbook is closed'),
                    'type'     => 'alert-danger',
                    'back_url' => '/',
                ]
            );
        }

        $errors = $this->session->getFlash('errors') ?? [];

        if ($this->request->getMethod() === 'POST' && $this->access->canWrite()) {
            $formData = $this->form->getFormData();
            $validator = new Validator($formData, $this->form->getValidationRules());
            if ($validator->isValid()) {
                $this->createEntry->execute(
                    new CreateGuestbookEntryDTO(
                        adminClub:     $this->mode->isAdminClub(),
                        name:          $this->user->isValid() ? $this->user->name : $formData['name'],
                        text:          $formData['message'],
                        ip:            $this->environment->getIp(false),
                        userAgent:     $this->environment->getUserAgent(),
                        attachedFiles: $formData['attached_files'],
                    )
                );
                $this->captchaService->forget();
                $this->session->flash('message', __('Your message was added successfully'));
                redirect($baseUrl);
            }
            $errors = $validator->getErrors();
        }

        $pagination = $this->paginationFactory->create($this->guestbookEntries->count());

        if ($this->request->getMethod() !== 'POST') {
            $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
            if ($redirectUrl !== null) {
                redirect($redirectUrl);
            }
        }

        $meta = new PageMeta($pageTitle, $pagination->getCurrentPage());
        $this->render->addData([
            'title'       => $meta->title,
            'page_title'  => $pageTitle,
            'description' => $meta->description,
        ]);

        $posts = $this->guestbookEntries->getPage($pagination->getPerPage(), $pagination->getOffset());
        $showCaptcha = $this->access->canWrite() && ! $this->user->isValid();

        return $this->render->render(
            'guestbook::index',
            [
                'posts'      => $posts,
                'pagination' => $pagination->render(),
                'isClosed'   => $this->access->isClosed(),
                'canWrite'   => $this->access->canWrite(),
                'canClear'   => $this->access->canClear(),
                'errors'     => $errors,
                'formData'   => $this->form->getFormData(),
                'captcha'    => $showCaptcha ? $this->captchaService->generate() : '',
                'message'    => $this->session->getFlash('message'),
            ]
        );
    }
}
