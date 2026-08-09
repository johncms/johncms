<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Users;

use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Admin\Application\Services\RegistrationRowMapper;
use Johncms\Modules\Admin\Application\UseCases\ApproveRegistrationUseCase;
use Johncms\Modules\Admin\Application\UseCases\DeleteRegistrationUseCase;
use Johncms\Modules\Admin\Application\UseCases\GetPendingRegistrationsUseCase;
use Johncms\Http\View\ViewResponse;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Users\User;
use Johncms\Validator\Validator;

final readonly class RegistrationModerationController
{
    private const URL = '/admin/registrations';

    public function __construct(
        private NavChain $navChain,
        private User $currentUser,
        private GetPendingRegistrationsUseCase $getPendingRegistrations,
        private ApproveRegistrationUseCase $approveRegistration,
        private DeleteRegistrationUseCase $deleteRegistration,
        private RegistrationRowMapper $rowMapper,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
        private Session $session,
    ) {
    }

    public function index(): ViewResponse
    {
        $pagination = $this->paginationFactory->create($this->getPendingRegistrations->count());

        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $registrations = $this->getPendingRegistrations->getPage($pagination->getPerPage(), $pagination->getOffset());

        $title = __('Registration confirmation');
        $this->navChain->add($title);

        $meta = new PageMeta($title, $pagination->getCurrentPage());

        return new ViewResponse(
            '@admin/registrations.twig',
            [
                'title'           => $meta->title,
                'page_title'      => $title,
                'usr_menu'        => ['reg' => true],
                'items'           => $this->rowMapper->mapMany($registrations),
                'total'           => $pagination->getTotal(),
                'per_page'        => $pagination->getPerPage(),
                'form_action'     => self::URL,
                'success_message' => (string) $this->session->getFlash('success_message'),
                'pagination'      => $pagination->render(),
            ]
        );
    }

    public function approve(Request $request): ViewResponse
    {
        if ($this->isCsrfValid($request) && ($id = $this->postedId($request)) > 0) {
            $this->approveRegistration->execute($id, $this->currentUser->name);
            $this->session->flash('success_message', __('Registration is confirmed'));
        }

        redirect(self::URL);
    }

    public function approveAll(Request $request): ViewResponse
    {
        if ($this->isCsrfValid($request)) {
            $this->approveRegistration->executeAll($this->currentUser->name);
            $this->session->flash('success_message', __('Registration is confirmed'));
        }

        redirect(self::URL);
    }

    public function delete(Request $request): ViewResponse
    {
        if ($this->isCsrfValid($request) && ($id = $this->postedId($request)) > 0) {
            $this->deleteRegistration->execute($id);
            $this->session->flash('success_message', __('User deleted'));
        }

        redirect(self::URL);
    }

    public function deleteAll(Request $request): ViewResponse
    {
        if ($this->isCsrfValid($request)) {
            $this->deleteRegistration->executeAll();
            $this->session->flash('success_message', __('All unconfirmed registrations were removed'));
        }

        redirect(self::URL);
    }

    public function deleteByIp(Request $request): ViewResponse
    {
        if ($this->isCsrfValid($request)) {
            $ip = $request->bodyInt('ip');
            if ($ip > 0) {
                $this->deleteRegistration->executeByIp($ip);
                $this->session->flash('success_message', __('All unconfirmed registrations with selected IP were deleted'));
            }
        }

        redirect(self::URL);
    }

    private function postedId(Request $request): int
    {
        return $request->bodyInt('id');
    }

    private function isCsrfValid(Request $request): bool
    {
        $validator = new Validator(
            ['csrf_token' => $request->body('csrf_token', '')],
            ['csrf_token' => ['Csrf']]
        );

        return $validator->isValid();
    }
}
