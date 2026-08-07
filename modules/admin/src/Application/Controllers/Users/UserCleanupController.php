<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Users;

use Johncms\Http\Controller\AdminControllerContext;
use Johncms\Http\Session;
use Johncms\Modules\Admin\Application\UseCases\CleanupInactiveUsersUseCase;
use Johncms\Modules\Admin\Domain\Repository\InactiveUsersRepositoryInterface;
use Johncms\Http\View\ViewResponse;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Validator\Validator;

final readonly class UserCleanupController
{
    private const URL = '/admin/users/cleanup';

    public function __construct(
        private AdminControllerContext $controllerContext,
        private NavChain $navChain,
        private InactiveUsersRepositoryInterface $inactiveUsers,
        private CleanupInactiveUsersUseCase $cleanupInactiveUsers,
        private Session $session,
    ) {
        $this->controllerContext->initModule('admin');
    }

    public function index(): ViewResponse
    {
        return $this->renderConfirm();
    }

    public function clean(Request $request): ViewResponse
    {
        if (! $this->isCsrfValid($request)) {
            return $this->renderConfirm(__('Wrong data'));
        }

        $deleted = $this->cleanupInactiveUsers->execute();
        $this->session->flash('success_message', __('Inactive profiles deleted') . ': ' . $deleted);
        redirect(self::URL);
    }

    private function isCsrfValid(Request $request): bool
    {
        $validator = new Validator(
            ['csrf_token' => $request->body('csrf_token', '')],
            ['csrf_token' => ['Csrf']]
        );

        return $validator->isValid();
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
