<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Settings;

use Johncms\Http\Controller\AdminControllerContext;
use Johncms\Modules\Admin\Application\DTO\SystemSettingsDTO;
use Johncms\Modules\Admin\Application\UseCases\UpdateSystemSettingsUseCase;
use Johncms\Modules\Admin\Domain\Exceptions\ConfigWriteException;
use Johncms\Modules\Admin\Domain\Services\ThemeListProviderInterface;
use Johncms\NavChain;
use Johncms\Http\Request;
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
        $currentHost = 'https://' . $this->request->server->getString('HTTP_HOST', '');

        return new SystemSettingsDTO(
            theme: $this->request->body('skindef', 'default'),
            email: $this->request->body('madm', 'example@example.com'),
            timeShift: $this->request->bodyInt('timeshift'),
            copyright: $this->request->body('copyright', 'JohnCMS'),
            homeUrl: $this->request->body('homeurl', $currentHost),
            maxFileSize: $this->request->bodyInt('flsz'),
            gzip: $this->request->bodyInt('gz'),
            metaTitle: $this->request->body('meta_title', 'johncms'),
            metaKeywords: $this->request->body('meta_key', 'johncms'),
            metaDescription: $this->request->body('meta_desc', 'johncms'),
            userEmailRequired: $this->request->bodyInt('user_email_required'),
            userEmailConfirmation: $this->request->bodyInt('user_email_confirmation'),
            privacyPolicyUrl: trim($this->request->body('privacy_policy_url', '')),
            termsOfUseUrl: trim($this->request->body('terms_of_use_url', '')),
            personalDataPolicyUrl: trim($this->request->body('personal_data_policy_url', '')),
            cookiePolicyUrl: trim($this->request->body('cookie_policy_url', '')),
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
