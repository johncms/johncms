<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Settings;

use Johncms\Http\Session;
use Johncms\Modules\Admin\Application\DTO\ModulesAccessDTO;
use Johncms\Modules\Admin\Application\UseCases\UpdateModulesAccessUseCase;
use Johncms\Modules\Admin\Domain\Exceptions\ConfigWriteException;
use Johncms\Modules\Registration\Application\Services\RegistrationSettings;
use Johncms\Http\View\ViewResponse;
use Johncms\NavChain;
use Johncms\Http\Request;

final readonly class ModulesAccessController
{
    private const URL = '/admin/modules-access';

    public function __construct(
        private NavChain $navChain,
        private UpdateModulesAccessUseCase $updateModulesAccessUseCase,
        private RegistrationSettings $registrationSettings,
        private Session $session,
    ) {
    }

    public function form(): ViewResponse
    {
        return $this->renderForm();
    }

    public function save(Request $request): ViewResponse
    {
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
            registrationModeration: (bool) $request->bodyInt('reg_moderation'),
            libraryComments: (bool) $request->bodyInt('libcomm'),
            downloadsComments: (bool) $request->bodyInt('downcomm'),
        );
    }

    private function renderForm(string $errorMessage = ''): ViewResponse
    {
        $title = __('Permissions');
        $this->navChain->add($title);

        return new ViewResponse(
            '@admin/modules-access.twig',
            [
                'title'           => $title,
                'page_title'      => $title,
                'module_menu'     => ['access' => true],
                'groups'          => $this->groups(),
                'form_action'     => self::URL,
                'error_message'   => $errorMessage,
                'success_message' => (string) $this->session->getFlash('success_message'),
            ]
        );
    }

    /**
     * What is left of this screen: the switches that are not permissions of anybody. Each row may
     * carry a set of modes and a toggle; the modes are gone from every module whose access became
     * a permission, and the screen disappears once the toggles have a home of their own.
     *
     * @return list<array<string, mixed>>
     */
    private function groups(): array
    {
        // Who may open a module is a permission of the guest and the user roles now, edited in
        // the role editor; what is left here of the modules that have comments is the toggle for
        // those, which is a feature of the module rather than a right of anybody.
        $allowed = ['value' => 2, 'label' => __('Access is allowed')];
        $authorized = ['value' => 1, 'label' => __('Only for authorized')];
        $denied = ['value' => 0, 'label' => __('Access denied')];

        return [
            [
                'title'    => __('Library'),
                'name'     => 'lib',
                'value'    => null,
                'options'  => [],
                'toggle'   => ['name' => 'libcomm', 'value' => (bool) config('johncms.mod_lib_comm', false), 'label' => __('Comments')],
            ],
            [
                'title'    => __('Downloads'),
                'name'     => 'down',
                'value'    => null,
                'options'  => [],
                'toggle'   => ['name' => 'downcomm', 'value' => (bool) config('johncms.mod_down_comm', false), 'label' => __('Comments')],
            ],
            [
                'title'    => __('Registration'),
                'name'     => 'reg',
                'value'    => null,
                'options'  => [],
                'toggle'   => [
                    'name'  => 'reg_moderation',
                    'value' => $this->registrationSettings->moderationEnabled(),
                    'label' => __('With moderation'),
                ],
            ],
        ];
    }
}
