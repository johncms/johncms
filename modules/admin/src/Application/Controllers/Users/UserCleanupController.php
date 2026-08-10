<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Users;

use Johncms\Http\Session;
use Johncms\Modules\Admin\Application\UseCases\CleanupInactiveUsersUseCase;
use Johncms\Modules\Admin\Domain\Repository\InactiveUsersRepositoryInterface;
use Johncms\Http\View\ViewResponse;
use Johncms\NavChain;
use Johncms\Http\Request;

final readonly class UserCleanupController
{
    private const URL = '/admin/users/cleanup';

    public function __construct(
        private NavChain $navChain,
        private InactiveUsersRepositoryInterface $inactiveUsers,
        private CleanupInactiveUsersUseCase $cleanupInactiveUsers,
        private Session $session,
    ) {
    }

    public function index(): ViewResponse
    {
        return $this->renderConfirm();
    }

    public function clean(): ViewResponse
    {
        $deleted = $this->cleanupInactiveUsers->execute();
        $this->session->flash('success_message', __('Inactive profiles deleted') . ': ' . $deleted);
        redirect(self::URL);
    }

    private function renderConfirm(?string $errorMessage = null): ViewResponse
    {
        $title = __('Database cleanup');
        $this->navChain->add($title);

        return new ViewResponse(
            '@admin/users-cleanup.twig',
            [
                'title'           => $title,
                'page_title'      => $title,
                'usr_menu'        => ['usr_clean' => true],
                'total'           => $this->inactiveUsers->countInactive(),
                'form_action'     => self::URL,
                'error_message'   => $errorMessage,
                'success_message' => (string) $this->session->getFlash('success_message'),
            ]
        );
    }
}
