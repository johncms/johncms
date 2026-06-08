<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Settings;

use Johncms\Http\Controller\AdminControllerContext;
use Johncms\Modules\Admin\Application\DTO\SystemSettingsDTO;
use Johncms\Modules\Admin\Application\UseCases\UpdateSystemSettingsUseCase;
use Johncms\Modules\Admin\Domain\Exceptions\ConfigWriteException;
use Johncms\Modules\Admin\Domain\Services\ThemeListProviderInterface;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;
use Johncms\Validator\Validator;

final readonly class SystemSettingsController
{
    private const URL = '/admin/settings';

    public function __construct(
        private AdminControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private UpdateSystemSettingsUseCase $updateSystemSettingsUseCase,
        private ThemeListProviderInterface $themeListProvider,
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
            $this->updateSystemSettingsUseCase->execute($this->buildDto());
        } catch (ConfigWriteException) {
            return $this->renderForm(__('ERROR: Can not write file `system.local.php`'));
        }

        $_SESSION['success_message'] = __('Settings are saved successfully');
        redirect(self::URL);
    }

    private function buildDto(): SystemSettingsDTO
    {
        $currentHost = 'https://' . $this->request->getServer('HTTP_HOST', '');

        return new SystemSettingsDTO(
            theme: (string) $this->request->getPost('skindef', 'default'),
            email: (string) $this->request->getPost('madm', 'example@example.com'),
            timeShift: (int) $this->request->getPost('timeshift', 0, FILTER_VALIDATE_INT),
            copyright: (string) $this->request->getPost('copyright', 'JohnCMS'),
            homeUrl: (string) $this->request->getPost('homeurl', $currentHost),
            maxFileSize: (int) $this->request->getPost('flsz', 0, FILTER_VALIDATE_INT),
            gzip: (int) $this->request->getPost('gz', 0, FILTER_VALIDATE_INT),
            metaTitle: (string) $this->request->getPost('meta_title', 'johncms'),
            metaKeywords: (string) $this->request->getPost('meta_key', 'johncms'),
            metaDescription: (string) $this->request->getPost('meta_desc', 'johncms'),
            userEmailRequired: (int) $this->request->getPost('user_email_required', 0, FILTER_VALIDATE_INT),
            userEmailConfirmation: (int) $this->request->getPost('user_email_confirmation', 0, FILTER_VALIDATE_INT),
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
        $title = __('System Settings');
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
                'sys_menu'   => ['settings' => true],
            ]
        );

        return $this->render->render(
            'admin::settings',
            [
                'sysconf'         => config('johncms'),
                'themelist'       => $this->themeListProvider->getAvailable(),
                'form_action'     => self::URL,
                'error_message'   => $errorMessage,
                'success_message' => $successMessage,
            ]
        );
    }
}
