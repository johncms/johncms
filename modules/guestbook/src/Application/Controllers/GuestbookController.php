<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\PageMeta;
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
    ) {
        $this->controllerContext->initModule('guestbook');
    }

    public function __invoke(): string
    {
        $pageTitle = $this->mode->isGuestbook() ? __('Guestbook') : __('Admin Club');
        $page = max(1, (int) $this->request->getQuery('page', 1));
        $meta = new PageMeta($pageTitle, $page);
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

        $this->render->addData([
            'title'       => $meta->title,
            'page_title'  => $pageTitle,
            'description' => $meta->description,
        ]);

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

        $posts = $this->guestbookEntries->execute();
        $showCaptcha = $this->access->canWrite() && ! $this->user->isValid();

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
                'captcha'    => $showCaptcha ? $this->captchaService->generate() : '',
                'message'    => $this->session->getFlash('message'),
            ]
        );
    }
}
