<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Users;

use Johncms\Http\Controller\AdminControllerContext;
use Johncms\Http\PageMeta;
use Johncms\Modules\Admin\Application\Services\RegistrationRowMapper;
use Johncms\Modules\Admin\Application\UseCases\ApproveRegistrationUseCase;
use Johncms\Modules\Admin\Application\UseCases\DeleteRegistrationUseCase;
use Johncms\Modules\Admin\Application\UseCases\GetPendingRegistrationsUseCase;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\Legacy\Tools;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Johncms\Validator\Validator;

final readonly class RegistrationModerationController
{
    private const URL = '/admin/registrations';

    public function __construct(
        private AdminControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private Tools $tools,
        private User $currentUser,
        private GetPendingRegistrationsUseCase $getPendingRegistrations,
        private ApproveRegistrationUseCase $approveRegistration,
        private DeleteRegistrationUseCase $deleteRegistration,
        private RegistrationRowMapper $rowMapper,
    ) {
        $this->controllerContext->initModule('admin');
    }

    public function index(): string
    {
        $page = max(1, (int) $this->request->getQuery('page', 1));
        $perPage = $this->currentUser->config->kmess;

        $registrations = $this->getPendingRegistrations->execute($page, $perPage);
        $total = $registrations->total();

        $title = __('Registration confirmation');
        $this->navChain->add($title);

        $successMessage = null;
        if (! empty($_SESSION['success_message'])) {
            $successMessage = (string) $_SESSION['success_message'];
            unset($_SESSION['success_message']);
        }

        $meta = new PageMeta($title, $page);
        $this->render->addData(
            [
                'title'      => $meta->title,
                'page_title' => $title,
                'usr_menu'   => ['reg' => true],
            ]
        );

        return $this->render->render(
            'admin::reg_list',
            [
                'items'           => $this->rowMapper->mapMany($registrations->getCollection()),
                'total'           => $total,
                'per_page'        => $perPage,
                'form_action'     => self::URL,
                'success_message' => $successMessage,
                'pagination'      => $this->tools->displayPagination(
                    self::URL . '?',
                    ($page - 1) * $perPage,
                    $total,
                    $perPage
                ),
            ]
        );
    }

    public function approve(): string
    {
        if ($this->isCsrfValid() && ($id = $this->postedId()) > 0) {
            $this->approveRegistration->execute($id, $this->currentUser->name);
            $_SESSION['success_message'] = __('Registration is confirmed');
        }

        redirect(self::URL);
    }

    public function approveAll(): string
    {
        if ($this->isCsrfValid()) {
            $this->approveRegistration->executeAll($this->currentUser->name);
            $_SESSION['success_message'] = __('Registration is confirmed');
        }

        redirect(self::URL);
    }

    public function delete(): string
    {
        if ($this->isCsrfValid() && ($id = $this->postedId()) > 0) {
            $this->deleteRegistration->execute($id);
            $_SESSION['success_message'] = __('User deleted');
        }

        redirect(self::URL);
    }

    public function deleteAll(): string
    {
        if ($this->isCsrfValid()) {
            $this->deleteRegistration->executeAll();
            $_SESSION['success_message'] = __('All unconfirmed registrations were removed');
        }

        redirect(self::URL);
    }

    public function deleteByIp(): string
    {
        if ($this->isCsrfValid()) {
            $ip = (int) $this->request->getPost('ip', 0, FILTER_VALIDATE_INT);
            if ($ip > 0) {
                $this->deleteRegistration->executeByIp($ip);
                $_SESSION['success_message'] = __('All unconfirmed registrations with selected IP were deleted');
            }
        }

        redirect(self::URL);
    }

    private function postedId(): int
    {
        return (int) $this->request->getPost('id', 0, FILTER_VALIDATE_INT);
    }

    private function isCsrfValid(): bool
    {
        $validator = new Validator(
            ['csrf_token' => (string) $this->request->getPost('csrf_token', '')],
            ['csrf_token' => ['Csrf']]
        );

        return $validator->isValid();
    }
}
