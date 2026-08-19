<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Settings;

use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Admin\Application\DTO\CaptchaSettingsDTO;
use Johncms\Modules\Admin\Application\UseCases\GetCaptchaSettingsUseCase;
use Johncms\Modules\Admin\Application\UseCases\UpdateCaptchaSettingsUseCase;
use Johncms\Modules\Admin\Domain\Exceptions\ConfigWriteException;
use Johncms\NavChain;

/**
 * Which captcha the site uses, and the settings of each of them.
 *
 * The page lists whatever is registered — the built-in picture, the shipped services, and the
 * providers of installed modules — and draws the fields each of them declares. Installing a
 * module with a captcha of its own is therefore all it takes to see it here.
 */
final readonly class CaptchaSettingsController
{
    private const URL = '/admin/settings/captcha';

    public function __construct(
        private NavChain $navChain,
        private Session $session,
        private GetCaptchaSettingsUseCase $getSettings,
        private UpdateCaptchaSettingsUseCase $updateSettings,
    ) {
    }

    public function form(): ViewResponse
    {
        return $this->renderForm();
    }

    public function save(Request $request): ViewResponse
    {
        /** @var array<string, array<string, string>> $providers */
        $providers = $request->request->all('providers');

        try {
            $this->updateSettings->execute(
                new CaptchaSettingsDTO(default: $request->body('default', ''), providers: $providers)
            );
        } catch (ConfigWriteException) {
            return $this->renderForm(__('ERROR: Can not write file `captcha.local.php`'));
        }

        $this->session->flash('success_message', __('Settings are saved successfully'));

        redirect(self::URL);
    }

    private function renderForm(string $errorMessage = ''): ViewResponse
    {
        $title = __('Captcha');
        $this->navChain->add($title);

        return new ViewResponse(
            '@admin/captcha-settings.twig',
            [
                'title'           => $title,
                'page_title'      => $title,
                'sys_menu'        => ['captcha_settings' => true],
                'form_action'     => self::URL,
                'providers'       => $this->getSettings->execute(),
                'error_message'   => $errorMessage,
                'success_message' => (string) $this->session->getFlash('success_message'),
            ]
        );
    }
}
