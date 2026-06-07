<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Settings;

use Johncms\Http\Controller\AdminControllerContext;
use Johncms\Modules\Admin\Application\DTO\ModulesAccessDTO;
use Johncms\Modules\Admin\Application\UseCases\UpdateModulesAccessUseCase;
use Johncms\Modules\Admin\Domain\Exceptions\ConfigWriteException;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;
use Johncms\Validator\Validator;

final readonly class ModulesAccessController
{
    private const URL = '/admin/modules-access';

    public function __construct(
        private AdminControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private UpdateModulesAccessUseCase $updateModulesAccessUseCase,
    ) {
        $this->controllerContext->initModule('admin');
    }

    public function form(): string
    {
        return $this->renderForm();
    }

    public function save(): string
    {
        if (! $this->isCsrfValid()) {
            return $this->renderForm(__('Wrong data'));
        }

        try {
            $this->updateModulesAccessUseCase->execute($this->buildDto());
        } catch (ConfigWriteException) {
            return $this->renderForm(__('ERROR: Can not write file `system.local.php`'));
        }

        $_SESSION['success_message'] = __('Settings are saved successfully');
        redirect(self::URL);
    }

    private function buildDto(): ModulesAccessDTO
    {
        return new ModulesAccessDTO(
            registration: (int) $this->request->getPost('reg', 0, FILTER_VALIDATE_INT),
            forum: (int) $this->request->getPost('forum', 0, FILTER_VALIDATE_INT),
            guestbook: (int) $this->request->getPost('guest', 0, FILTER_VALIDATE_INT),
            library: (int) $this->request->getPost('lib', 0, FILTER_VALIDATE_INT),
            libraryComments: (bool) $this->request->getPost('libcomm', 0, FILTER_VALIDATE_INT),
            downloads: (int) $this->request->getPost('down', 0, FILTER_VALIDATE_INT),
            downloadsComments: (bool) $this->request->getPost('downcomm', 0, FILTER_VALIDATE_INT),
            community: (int) $this->request->getPost('active', 0, FILTER_VALIDATE_INT),
        );
    }

    private function isCsrfValid(): bool
    {
        $validator = new Validator(
            ['csrf_token' => (string) $this->request->getPost('csrf_token', '')],
            ['csrf_token' => ['Csrf']]
        );

        return $validator->isValid();
    }

    private function renderForm(?string $errorMessage = null): string
    {
        $title = __('Permissions');
        $this->navChain->add($title);

        $successMessage = null;
        if (! empty($_SESSION['success_message'])) {
            $successMessage = (string) $_SESSION['success_message'];
            unset($_SESSION['success_message']);
        }

        $this->render->addData(
            [
                'title'       => $title,
                'page_title'  => $title,
                'module_menu' => ['access' => true],
            ]
        );

        return $this->render->render(
            'admin::access',
            [
                'conf'            => config('johncms'),
                'form_action'     => self::URL,
                'error_message'   => $errorMessage,
                'success_message' => $successMessage,
            ]
        );
    }
}
