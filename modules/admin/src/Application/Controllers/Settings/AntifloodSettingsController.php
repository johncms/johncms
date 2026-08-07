<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Settings;

use Johncms\Http\Controller\AdminControllerContext;
use Johncms\Modules\Admin\Application\DTO\AntifloodSettingsDTO;
use Johncms\Modules\Admin\Application\UseCases\UpdateAntifloodSettingsUseCase;
use Johncms\Modules\Admin\Domain\Exceptions\ConfigWriteException;
use Johncms\Http\View\ViewResponse;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Validator\Validator;

final readonly class AntifloodSettingsController
{
    private const URL = '/admin/antiflood';

    public function __construct(
        private AdminControllerContext $controllerContext,
        private NavChain $navChain,
        private UpdateAntifloodSettingsUseCase $updateAntifloodSettingsUseCase,
        private Session $session,
    ) {
        $this->controllerContext->initModule('admin');
    }

    public function form(): ViewResponse
    {
        return $this->renderForm();
    }

    public function save(Request $request): ViewResponse
    {
        if (! $this->isCsrfValid($request)) {
            return $this->renderForm(__('Wrong data'));
        }

        try {
            $this->updateAntifloodSettingsUseCase->execute($this->buildDto($request));
        } catch (ConfigWriteException) {
            return $this->renderForm(__('ERROR: Can not write file `system.local.php`'));
        }

        $this->session->flash('success_message', __('Settings are saved successfully'));
        redirect(self::URL);
    }

    private function buildDto(Request $request): AntifloodSettingsDTO
    {
        return new AntifloodSettingsDTO(
            mode: $request->bodyInt('mode', 1),
            day: $request->bodyInt('day', 10),
            night: $request->bodyInt('night', 30),
            dayFrom: $request->bodyInt('dayfrom', 10),
            dayTo: $request->bodyInt('dayto', 22),
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

    private function renderForm(string $errorMessage = ''): ViewResponse
    {
        $title = __('Antiflood Settings');
        $this->navChain->add($title);

        return new ViewResponse(
            '@admin/antiflood.twig',
            [
                'title'           => $title,
                'page_title'      => $title,
                'usr_menu'        => ['antiflood' => true],
                'settings'        => (array) config('johncms.antiflood', []),
                'mode_options'    => $this->modeOptions(),
                'form_action'     => self::URL,
                'error_message'   => $errorMessage,
                'success_message' => (string) $this->session->getFlash('success_message'),
            ]
        );
    }

    /**
     * @return list<array{value: int, label: string, hint: string}>
     */
    private function modeOptions(): array
    {
        return [
            ['value' => 3, 'label' => __('Day'), 'hint' => ''],
            ['value' => 4, 'label' => __('Night'), 'hint' => ''],
            [
                'value' => 2,
                'label' => __('Day / Night'),
                'hint'  => __('Automatic change from day to night mode, according to specified time set'),
            ],
            [
                'value' => 1,
                'label' => __('Adaptive'),
                'hint'  => __('If one of administration is online (on the site), the system work in &quot;day&quot; mode, if administration is offline, it switch to &quot;night&quot;'),
            ],
        ];
    }
}
