<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Users;

use Johncms\Auth\CurrentUser;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Admin\Application\Services\UserListControlsMapper;
use Johncms\Modules\Admin\Application\UseCases\ApproveRegistrationUseCase;
use Johncms\Modules\Admin\Application\UseCases\ConfirmUserEmailUseCase;
use Johncms\Modules\Admin\Application\UseCases\DeleteRegistrationUseCase;
use Johncms\Modules\Admin\Application\UseCases\GetPendingRegistrationsUseCase;
use Johncms\NavChain;

/**
 * What is done to the accounts waiting for an administrator to let them in, or for their owner to
 * confirm the e-mail. They are listed by the list of users (status=pending, status=unconfirmed),
 * where the menu of each row and the head of the list lead here.
 *
 * Removing is confirmed first, in a dialog saying what would go: the modal of the panel asks for
 * it with X-Requested-With and gets the dialog alone, a plain visit gets it as a page. Every
 * action returns to the page of the list it was taken from ("back").
 */
final readonly class RegistrationModerationController
{
    /**
     * Where an action returns to unless told otherwise: the accounts still waiting.
     */
    private const URL = '/admin/users?status=pending';

    public function __construct(
        private NavChain $navChain,
        private CurrentUser $currentUser,
        private ApproveRegistrationUseCase $approveRegistration,
        private ConfirmUserEmailUseCase $confirmUserEmail,
        private DeleteRegistrationUseCase $deleteRegistration,
        private GetPendingRegistrationsUseCase $pending,
        private UserListControlsMapper $controls,
        private Session $session,
    ) {
    }

    public function approve(Request $request): ViewResponse
    {
        if (($id = $this->postedId($request)) > 0) {
            $this->approveRegistration->execute($id, $this->currentUser->user()->name);
            $this->session->flash('success_message', __('Registration is confirmed'));
        }

        redirect($this->returnUrl($request->body('back')));
    }

    public function confirmEmail(Request $request): ViewResponse
    {
        if (($id = $this->postedId($request)) > 0) {
            $this->confirmUserEmail->execute($id);
            $this->session->flash('success_message', __('The e-mail is confirmed'));
        }

        redirect($this->returnUrl($request->body('back')));
    }

    public function approveAll(Request $request): ViewResponse
    {
        $this->approveRegistration->executeAll($this->currentUser->user()->name);
        $this->session->flash('success_message', __('Registration is confirmed'));

        redirect($this->returnUrl($request->body('back')));
    }

    public function deleteConfirm(Request $request, int $id): ViewResponse
    {
        $user = $this->pending->find($id);

        if ($user === null) {
            return $this->nothingToDelete($request, __('The account is not waiting for approval any more'));
        }

        return $this->confirm($request, [
            'title'   => __('Delete the registration'),
            'message' => __('The account is removed for good. Nobody has let it in yet, so it leaves nothing behind.'),
            'user'    => ['id' => $user->id, 'name' => $user->name],
            'action'  => '/admin/registrations/delete',
            'fields'  => ['id' => $user->id],
            'confirm' => __('Delete'),
        ]);
    }

    public function delete(Request $request): ViewResponse
    {
        if (($id = $this->postedId($request)) > 0) {
            $this->deleteRegistration->execute($id);
            $this->session->flash('success_message', __('User deleted'));
        }

        redirect($this->returnUrl($request->body('back')));
    }

    public function deleteAllConfirm(Request $request): ViewResponse
    {
        $count = $this->pending->count();

        if ($count === 0) {
            return $this->nothingToDelete($request, __('Nobody is waiting for approval'));
        }

        return $this->confirm($request, [
            'title'   => __('Delete all registrations'),
            'message' => sprintf(n__('%d account waiting for approval is removed for good.', '%d accounts waiting for approval are removed for good.', $count), $count),
            'user'    => [],
            'action'  => '/admin/registrations/delete-all',
            'fields'  => [],
            'confirm' => __('Delete all'),
        ]);
    }

    public function deleteAll(Request $request): ViewResponse
    {
        $this->deleteRegistration->executeAll();
        $this->session->flash('success_message', __('All unconfirmed registrations were removed'));

        redirect($this->returnUrl($request->body('back')));
    }

    public function deleteByIpConfirm(Request $request): ViewResponse
    {
        $ip = $request->queryInt('ip');
        $count = $this->pending->countByIp($ip);

        if ($count === 0) {
            return $this->nothingToDelete($request, __('Nobody from this address is waiting for approval'));
        }

        return $this->confirm($request, [
            'title'   => __('Delete the registrations from an address'),
            'message' => sprintf(
                n__(
                    '%1$d account waiting for approval registered from %2$s is removed for good.',
                    '%1$d accounts waiting for approval registered from %2$s are removed for good.',
                    $count
                ),
                $count,
                long2ip($ip)
            ),
            'user'    => [],
            'action'  => '/admin/registrations/delete-by-ip',
            'fields'  => ['ip' => $ip],
            'confirm' => __('Delete'),
        ]);
    }

    public function deleteByIp(Request $request): ViewResponse
    {
        $ip = $request->bodyInt('ip');
        if ($ip > 0) {
            $this->deleteRegistration->executeByIp($ip);
            $this->session->flash('success_message', __('All unconfirmed registrations with selected IP were deleted'));
        }

        redirect($this->returnUrl($request->body('back')));
    }

    /**
     * @param array<string, mixed> $dialog What pages/confirm-dialog.twig is given, but for the way back.
     */
    private function confirm(Request $request, array $dialog): ViewResponse
    {
        $dialog['back_url'] = $this->returnUrl($request->queryParam('back'));

        if ($request->isXmlHttpRequest()) {
            return new ViewResponse('@admin/pages/confirm-dialog.twig', $dialog);
        }

        $this->navChain->add($dialog['title']);

        return new ViewResponse('@admin/pages/confirm.twig', ['title' => $dialog['title'], 'dialog' => $dialog]);
    }

    /**
     * What a confirmation shows when what it was about is gone: approved or removed meanwhile,
     * from another tab or by another administrator.
     */
    private function nothingToDelete(Request $request, string $message): ViewResponse
    {
        $title = __('Nothing to delete');
        $data = [
            'title'    => $title,
            'type'     => 'alert-info',
            'message'  => $message,
            'back_url' => $this->returnUrl($request->queryParam('back')),
        ];

        if ($request->isXmlHttpRequest()) {
            return new ViewResponse('@admin/pages/result-dialog.twig', $data);
        }

        $this->navChain->add($title);

        return new ViewResponse('@admin/pages/result.twig', $data + ['page_title' => $title]);
    }

    private function returnUrl(string $back): string
    {
        return $this->controls->returnUrl($back, self::URL);
    }

    private function postedId(Request $request): int
    {
        return $request->bodyInt('id');
    }
}
