<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Settings;

use Johncms\Http\Controller\AdminControllerContext;
use Johncms\Http\Session;
use Johncms\Modules\Admin\Application\DTO\ModulesAccessDTO;
use Johncms\Modules\Admin\Application\UseCases\UpdateModulesAccessUseCase;
use Johncms\Modules\Admin\Domain\Exceptions\ConfigWriteException;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\System\View\Render;
use Johncms\Validator\Validator;

final readonly class ModulesAccessController
{
    private const URL = '/admin/modules-access';

    public function __construct(
        private AdminControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private UpdateModulesAccessUseCase $updateModulesAccessUseCase,
        private Session $session,
    ) {
        $this->controllerContext->initModule('admin');
    }

    public function form(): string
    {
        return $this->renderForm();
    }

    public function save(Request $request): string
    {
        if (! $this->isCsrfValid($request)) {
            return $this->renderForm(__('Wrong data'));
        }

        try {
            $this->updateModulesAccessUseCase->execute($this->buildDto($request));
        } catch (ConfigWriteException) {
            return $this->renderForm(__('ERROR: Can not write file `system.local.php`'));
        }

        $this->session->flash('success_message', __('Settings are saved successfully'));
        redirect(self::URL);
    }

    private function buildDto(Request $request): ModulesAccessDTO
    {
        return new ModulesAccessDTO(
            registration: $request->bodyInt('reg'),
            forum: $request->bodyInt('forum'),
            guestbook: $request->bodyInt('guest'),
            library: $request->bodyInt('lib'),
            libraryComments: (bool) $request->bodyInt('libcomm'),
            downloads: $request->bodyInt('down'),
            downloadsComments: (bool) $request->bodyInt('downcomm'),
            community: $request->bodyInt('active'),
        );
    }

    private function isCsrfValid(Request $request): bool
    {
        $validator = new Validator(
            ['csrf_token' => $request->body('csrf_token', '')],
            ['csrf_token' => ['Csrf']]
        );

        return $validator->isValid();
    }

    private function renderForm(?string $errorMessage = null): string
    {
        $title = __('Permissions');
        $this->navChain->add($title);

        $successMessage = $this->session->getFlash('success_message');

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
