<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Users;

use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Admin\Application\UseCases\CleanupInactiveUsersUseCase;
use Johncms\Modules\Admin\Domain\Repository\InactiveUsersRepositoryInterface;
use Johncms\NavChain;

/**
 * The cleanup of the accounts nobody uses. The page tells who goes and what goes with them; the
 * deletion is confirmed in a dialog — the modal of the panel asks for it with X-Requested-With
 * and gets the dialog alone, a plain visit gets it as a page.
 */
final readonly class UserCleanupController
{
    private const URL = '/admin/users/cleanup';
    private const CONFIRM_URL = '/admin/users/cleanup/confirm';

    public function __construct(
        private NavChain $navChain,
        private InactiveUsersRepositoryInterface $inactiveUsers,
        private CleanupInactiveUsersUseCase $cleanupInactiveUsers,
        private Session $session,
    ) {
    }

    public function index(): ViewResponse
    {
        $title = __('Database cleanup');
        $this->navChain->add($title);

        return new ViewResponse('@admin/users-cleanup.twig', [
            'title'           => $title,
            'page_title'      => $title,
            'usr_menu'        => ['usr_clean' => true],
            'total'           => $this->inactiveUsers->countInactive(),
            'confirm_url'     => self::CONFIRM_URL,
            'success_message' => (string) $this->session->getFlash('success_message'),
        ]);
    }

    public function confirm(Request $request): ViewResponse
    {
        $data = [
            'total'       => $this->inactiveUsers->countInactive(),
            'form_action' => self::URL,
            'back_url'    => self::URL,
        ];

        if ($request->isXmlHttpRequest()) {
            return new ViewResponse('@admin/users/cleanup-dialog.twig', $data);
        }

        $title = __('Delete inactive accounts');
        $this->navChain->add(__('Database cleanup'), self::URL);
        $this->navChain->add($title);

        return new ViewResponse('@admin/users-cleanup-confirm.twig', [
            // The dialog carries the heading itself.
            'title'    => $title,
            'usr_menu' => ['usr_clean' => true],
            'dialog'   => $data,
        ]);
    }

    public function clean(): ViewResponse
    {
        $deleted = $this->cleanupInactiveUsers->execute();
        $this->session->flash('success_message', sprintf(n__('%d account deleted', '%d accounts deleted', $deleted), $deleted));
        redirect(self::URL);
    }
}
