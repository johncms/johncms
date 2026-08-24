<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\Controllers;

use Johncms\Auth\CurrentUser;
use Johncms\Auth\External\ExternalAuthException;
use Johncms\Auth\External\UnlinkExternalIdentityUseCase;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Profile\Application\UseCases\GetLinkedAccountsUseCase;
use Johncms\NavChain;

/**
 * "Linked accounts": which external services sign this account in, and how to detach one.
 */
final readonly class LinkedAccountsController
{
    private const URL = '/profile/accounts';

    public function __construct(
        private NavChain $navChain,
        private Session $session,
        private CurrentUser $currentUser,
        private GetLinkedAccountsUseCase $getLinkedAccounts,
        private UnlinkExternalIdentityUseCase $unlink,
    ) {
    }

    public function index(): ViewResponse
    {
        $title = __('Linked accounts');
        $this->navChain->add(__('Personal'), '/profile/account');
        $this->navChain->add($title);

        $accounts = $this->getLinkedAccounts->execute();

        return new ViewResponse(
            '@profile/public/linked-accounts.twig',
            [
                'title'           => $title,
                'page_title'      => $title,
                'form_action'     => self::URL,
                'linked'          => $accounts['linked'],
                'available'       => $accounts['available'],
                'success_message' => (string) $this->session->getFlash('success_message'),
                'error_message'   => (string) $this->session->getFlash('error_message'),
            ]
        );
    }

    public function detach(Request $request): void
    {
        try {
            $this->unlink->execute($this->currentUser->id(), $request->body('provider', ''));
            $this->session->flash('success_message', __('The service has been detached'));
        } catch (ExternalAuthException $exception) {
            $this->session->flash('error_message', $exception->getMessage());
        }

        redirect(self::URL);
    }
}
