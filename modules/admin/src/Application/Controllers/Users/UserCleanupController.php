<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Users;

use Johncms\Http\Controller\AdminControllerContext;
use Johncms\Modules\Admin\Application\UseCases\CleanupInactiveUsersUseCase;
use Johncms\Modules\Admin\Domain\Repository\InactiveUsersRepositoryInterface;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;
use Johncms\Validator\Validator;

final readonly class UserCleanupController
{
    private const URL = '/admin/users/cleanup';

    public function __construct(
        private AdminControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private InactiveUsersRepositoryInterface $inactiveUsers,
        private CleanupInactiveUsersUseCase $cleanupInactiveUsers,
    ) {
        $this->controllerContext->initModule('admin');
    }

    public function index(): string
    {
        return $this->renderConfirm();
    }

    public function clean(): string
    {
        if (! $this->isCsrfValid()) {
            return $this->renderConfirm(__('Wrong data'));
        }

        $deleted = $this->cleanupInactiveUsers->execute();
        $_SESSION['success_message'] = __('Inactive profiles deleted') . ': ' . $deleted;
        redirect(self::URL);
    }

    private function isCsrfValid(): bool
    {
        $validator = new Validator(
            ['csrf_token' => (string) $this->request->getPost('csrf_token', '')],
            ['csrf_token' => ['Csrf']]
        );

        return $validator->isValid();
    }

    private function renderConfirm(?string $errorMessage = null): string
    {
        $title = __('Database cleanup');
        $this->navChain->add($title);

        $successMessage = null;
        if (! empty($_SESSION['success_message'])) {
            $successMessage = (string) $_SESSION['success_message'];
            unset($_SESSION['success_message']);
        }

        $this->render->addData(
            [
                'title'      => $title,
                'page_title' => $title,
                'usr_menu'   => ['usr_clean' => true],
            ]
        );

        return $this->render->render(
            'admin::user_clean_confirm',
            [
                'total'           => $this->inactiveUsers->countInactive(),
                'form_action'     => self::URL,
                'error_message'   => $errorMessage,
                'success_message' => $successMessage,
            ]
        );
    }
}
