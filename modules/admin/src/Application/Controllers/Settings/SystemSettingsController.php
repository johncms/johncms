<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Settings;

use Johncms\Http\Controller\AdminControllerContext;
use Johncms\Http\Session;
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
        private NavChain $navChain,
        private UpdateSystemSettingsUseCase $updateSystemSettingsUseCase,
        private ThemeListProviderInterface $themeListProvider,
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
            $this->updateSystemSettingsUseCase->execute($this->buildDto($request));
        } catch (ConfigWriteException) {
            return $this->renderForm(__('ERROR: Can not write file `system.local.php`'));
        }

        $this->session->flash('success_message', __('Settings are saved successfully'));
        redirect(self::URL);
    }

    private function buildDto(Request $request): SystemSettingsDTO
    {
        $currentHost = 'https://' . $request->server->getString('HTTP_HOST', '');

        return new SystemSettingsDTO(
            theme: $request->body('skindef', 'default'),
            email: $request->body('madm', 'example@example.com'),
            timeShift: $request->bodyInt('timeshift'),
            copyright: $request->body('copyright', 'JohnCMS'),
            homeUrl: $request->body('homeurl', $currentHost),
            maxFileSize: $request->bodyInt('flsz'),
            gzip: $request->bodyInt('gz'),
            metaTitle: $request->body('meta_title', 'johncms'),
            metaKeywords: $request->body('meta_key', 'johncms'),
            metaDescription: $request->body('meta_desc', 'johncms'),
            userEmailRequired: $request->bodyInt('user_email_required'),
            userEmailConfirmation: $request->bodyInt('user_email_confirmation'),
            privacyPolicyUrl: trim($request->body('privacy_policy_url', '')),
            termsOfUseUrl: trim($request->body('terms_of_use_url', '')),
            personalDataPolicyUrl: trim($request->body('personal_data_policy_url', '')),
            cookiePolicyUrl: trim($request->body('cookie_policy_url', '')),
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
        $title = __('System Settings');
        $this->navChain->add($title);

        $successMessage = $this->session->getFlash('success_message');

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
