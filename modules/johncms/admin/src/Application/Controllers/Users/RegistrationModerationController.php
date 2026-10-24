<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Users;

use Johncms\Auth\CurrentUser;
use Johncms\Modules\Admin\Application\UseCases\ApproveRegistrationUseCase;
use Johncms\Modules\Admin\Application\UseCases\DeleteRegistrationUseCase;
use Johncms\Http\View\ViewResponse;
use Johncms\Http\Request;
use Johncms\Http\Session;

/**
 * What is done to the accounts waiting for an administrator to let them in. They are listed by
 * the list of users (status=pending), where the menu of each row and the head of the list post
 * here.
 */
final readonly class RegistrationModerationController
{
    /**
     * Where an action returns to: the list of users narrowed to the accounts still waiting.
     */
    private const URL = '/admin/users?status=pending';

    public function __construct(
        private CurrentUser $currentUser,
        private ApproveRegistrationUseCase $approveRegistration,
        private DeleteRegistrationUseCase $deleteRegistration,
        private Session $session,
    ) {
    }

    public function approve(Request $request): ViewResponse
    {
        if (($id = $this->postedId($request)) > 0) {
            $this->approveRegistration->execute($id, $this->currentUser->user()->name);
            $this->session->flash('success_message', __('Registration is confirmed'));
        }

        redirect(self::URL);
    }

    public function approveAll(): ViewResponse
    {
        $this->approveRegistration->executeAll($this->currentUser->user()->name);
        $this->session->flash('success_message', __('Registration is confirmed'));

        redirect(self::URL);
    }

    public function delete(Request $request): ViewResponse
    {
        if (($id = $this->postedId($request)) > 0) {
            $this->deleteRegistration->execute($id);
            $this->session->flash('success_message', __('User deleted'));
        }

        redirect(self::URL);
    }

    public function deleteAll(): ViewResponse
    {
        $this->deleteRegistration->executeAll();
        $this->session->flash('success_message', __('All unconfirmed registrations were removed'));

        redirect(self::URL);
    }

    public function deleteByIp(Request $request): ViewResponse
    {
        $ip = $request->bodyInt('ip');
        if ($ip > 0) {
            $this->deleteRegistration->executeByIp($ip);
            $this->session->flash('success_message', __('All unconfirmed registrations with selected IP were deleted'));
        }

        redirect(self::URL);
    }

    private function postedId(Request $request): int
    {
        return $request->bodyInt('id');
    }
}
