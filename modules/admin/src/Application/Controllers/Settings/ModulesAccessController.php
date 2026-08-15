<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Settings;

use Johncms\Http\Session;
use Johncms\Modules\Admin\Application\DTO\ModulesAccessDTO;
use Johncms\Modules\Admin\Application\UseCases\UpdateModulesAccessUseCase;
use Johncms\Modules\Admin\Domain\Exceptions\ConfigWriteException;
use Johncms\Http\View\ViewResponse;
use Johncms\NavChain;
use Johncms\Http\Request;

final readonly class ModulesAccessController
{
    private const URL = '/admin/modules-access';

    public function __construct(
        private NavChain $navChain,
        private UpdateModulesAccessUseCase $updateModulesAccessUseCase,
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
            registration: $request->bodyInt('reg'),
            libraryComments: (bool) $request->bodyInt('libcomm'),
            downloadsComments: (bool) $request->bodyInt('downcomm'),
            community: $request->bodyInt('active'),
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
     * The modules whose access is managed here, each with the modes it offers and the mode it is
     * in. A module that also has comments carries the toggle for them.
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
                'comments' => ['name' => 'libcomm', 'value' => (bool) config('johncms.mod_lib_comm', false)],
            ],
            [
                'title'    => __('Downloads'),
                'name'     => 'down',
                'value'    => null,
                'options'  => [],
                'comments' => ['name' => 'downcomm', 'value' => (bool) config('johncms.mod_down_comm', false)],
            ],
            [
                'title'    => __('Community'),
                'name'     => 'active',
                'value'    => (int) config('johncms.active', 0),
                'options'  => [
                    ['value' => 1, 'label' => __('Access is allowed')],
                    ['value' => 0, 'label' => __('Only for authorized')],
                ],
                'comments' => null,
            ],
            [
                'title'    => __('Registration'),
                'name'     => 'reg',
                'value'    => (int) config('johncms.mod_reg', 0),
                'options'  => [$allowed, ['value' => 1, 'label' => __('With moderation')], $denied],
                'comments' => null,
            ],
        ];
    }
}
