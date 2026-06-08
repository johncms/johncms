<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Settings;

use Johncms\Http\Controller\AdminControllerContext;
use Johncms\Modules\Admin\Application\DTO\AntifloodSettingsDTO;
use Johncms\Modules\Admin\Application\UseCases\UpdateAntifloodSettingsUseCase;
use Johncms\Modules\Admin\Domain\Exceptions\ConfigWriteException;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;
use Johncms\Validator\Validator;

final readonly class AntifloodSettingsController
{
    private const URL = '/admin/antiflood';

    public function __construct(
        private AdminControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private UpdateAntifloodSettingsUseCase $updateAntifloodSettingsUseCase,
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
            $this->updateAntifloodSettingsUseCase->execute($this->buildDto());
        } catch (ConfigWriteException) {
            return $this->renderForm(__('ERROR: Can not write file `system.local.php`'));
        }

        $_SESSION['success_message'] = __('Settings are saved successfully');
        redirect(self::URL);
    }

    private function buildDto(): AntifloodSettingsDTO
    {
        return new AntifloodSettingsDTO(
            mode: (int) $this->request->getPost('mode', 1, FILTER_VALIDATE_INT),
            day: (int) $this->request->getPost('day', 10, FILTER_VALIDATE_INT),
            night: (int) $this->request->getPost('night', 30, FILTER_VALIDATE_INT),
            dayFrom: (int) $this->request->getPost('dayfrom', 10, FILTER_VALIDATE_INT),
            dayTo: (int) $this->request->getPost('dayto', 22, FILTER_VALIDATE_INT),
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
        $title = __('Antiflood Settings');
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
                'usr_menu'   => ['antiflood' => true],
            ]
        );

        return $this->render->render(
            'admin::antiflood',
            [
                'set_af'          => config('johncms')['antiflood'],
                'form_action'     => self::URL,
                'error_message'   => $errorMessage,
                'success_message' => $successMessage,
            ]
        );
    }
}
