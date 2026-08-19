<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\Controllers;

use Johncms\Auth\CurrentUser;
use Johncms\Captcha\CaptchaManager;
use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Guestbook\Application\Access\GuestbookAccess;
use Johncms\Modules\Guestbook\Application\Access\GuestbookMode;
use Johncms\Modules\Guestbook\Application\DTO\CreateGuestbookEntryDTO;
use Johncms\Modules\Guestbook\Application\Forms\GuestbookForm;
use Johncms\Modules\Guestbook\Application\UseCases\CreateGuestbookEntryUseCase;
use Johncms\Modules\Guestbook\Application\UseCases\ListGuestbookEntriesUseCase;
use Johncms\NavChain;
use Johncms\Http\Environment;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\Http\Session;
use Johncms\Validator\ValidatorInterface;

final readonly class GuestbookController
{
    public function __construct(
        private NavChain $navChain,
        private Session $session,
        private Environment $environment,
        private CurrentUser $currentUser,
        private GuestbookAccess $access,
        private GuestbookMode $mode,
        private ListGuestbookEntriesUseCase $guestbookEntries,
        private CreateGuestbookEntryUseCase $createEntry,
        private CaptchaManager $captcha,
        private GuestbookForm $form,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
        private ValidatorInterface $validator,
    ) {
    }

    public function __invoke(Request $request): ViewResponse
    {
        $pageTitle = $this->mode->isGuestbook() ? __('Guestbook') : __('Admin Club');
        $baseUrl = '/guestbook/';
        $this->navChain->add($pageTitle, $baseUrl);

        if (! $this->access->canRead()) {
            return new ViewResponse(
                '@theme/pages/result.twig',
                [
                    'title'    => $pageTitle,
                    'message'  => __('Guestbook is closed'),
                    'type'     => 'alert-danger',
                    'back_url' => '/',
                ]
            );
        }

        $errors = $this->session->getFlash('errors') ?? [];

        if ($request->getMethod() === 'POST' && $this->access->canWrite()) {
            $formData = $this->form->getFormData($request);
            $result = $this->validator->validate($formData, $this->form->getValidationRules());
            if ($result->isValid()) {
                $this->createEntry->execute(
                    new CreateGuestbookEntryDTO(
                        adminClub:     $this->mode->isAdminClub(),
                        name:          $this->currentUser->isValid() ? $this->currentUser->user()->name : $formData['name'],
                        text:          $formData['message'],
                        ip:            $this->environment->getIp(false),
                        userAgent:     $this->environment->getUserAgent(),
                        attachedFiles: $formData['attached_files'],
                    )
                );
                $this->session->flash('message', __('Your message was added successfully'));
                redirect($baseUrl);
            }
            $errors = $result->getErrors();
        }

        $pagination = $this->paginationFactory->create($this->guestbookEntries->count());

        if ($request->getMethod() !== 'POST') {
            $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
            if ($redirectUrl !== null) {
                redirect($redirectUrl);
            }
        }

        $meta = new PageMeta($pageTitle, $pagination->getCurrentPage());

        $posts = $this->guestbookEntries->getPage($pagination->getPerPage(), $pagination->getOffset());
        $showCaptcha = $this->access->canWrite() && ! $this->currentUser->isValid();

        return new ViewResponse(
            '@guestbook/public/index.twig',
            [
                'title'       => $meta->title,
                'page_title'  => $pageTitle,
                'description' => $meta->description,
                'posts'       => $posts,
                'pagination'  => $pagination->hasPages() ? $pagination->render() : null,
                'can_write'   => $this->access->canWrite(),
                'can_clear'   => $this->access->canClear(),
                'errors'      => $errors,
                'form_data'   => $this->form->getFormData($request),
                'captcha'     => $showCaptcha ? $this->captcha->challenge(GuestbookForm::CAPTCHA_SCOPE) : null,
                'message'     => $this->session->getFlash('message'),
            ]
        );
    }
}
