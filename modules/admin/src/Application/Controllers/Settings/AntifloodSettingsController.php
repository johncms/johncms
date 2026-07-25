<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Settings;

use Johncms\Http\Controller\AdminControllerContext;
use Johncms\Modules\Admin\Application\DTO\AntifloodSettingsDTO;
use Johncms\Modules\Admin\Application\UseCases\UpdateAntifloodSettingsUseCase;
use Johncms\Modules\Admin\Domain\Exceptions\ConfigWriteException;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\Session;
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
        private Session $session,
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

        $this->session->flash('success_message', __('Settings are saved successfully'));
        redirect(self::URL);
    }

    private function buildDto(): AntifloodSettingsDTO
    {
        return new AntifloodSettingsDTO(
            mode: $this->request->bodyInt('mode', 1),
            day: $this->request->bodyInt('day', 10),
            night: $this->request->bodyInt('night', 30),
            dayFrom: $this->request->bodyInt('dayfrom', 10),
            dayTo: $this->request->bodyInt('dayto', 22),
        );
    }

    private function isCsrfValid(): bool
    {
        $validator = new Validator(
            ['csrf_token' => $this->request->body('csrf_token', '')],
            ['csrf_token' => ['Csrf']]
        );

        return $validator->isValid();
    }

    private function renderForm(?string $errorMessage = null): string
    {
        $title = __('Antiflood Settings');
        $this->navChain->add($title);

        $successMessage = $this->session->getFlash('success_message');

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
