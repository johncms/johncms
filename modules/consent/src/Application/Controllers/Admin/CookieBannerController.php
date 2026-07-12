<?php

declare(strict_types=1);

namespace Johncms\Modules\Consent\Application\Controllers\Admin;

use Johncms\Http\Controller\AdminControllerContext;
use Johncms\Modules\Admin\Domain\Exceptions\ConfigWriteException;
use Johncms\Modules\Consent\Application\DTO\CookieBannerSettingsDTO;
use Johncms\Modules\Consent\Application\UseCases\UpdateCookieBannerSettingsUseCase;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;
use Johncms\Validator\Validator;

final readonly class CookieBannerController
{
    private const URL = '/admin/cookie-banner';

    public function __construct(
        private AdminControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private UpdateCookieBannerSettingsUseCase $updateCookieBannerSettings,
    ) {
        $this->controllerContext->initModule('consent');
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
            $this->updateCookieBannerSettings->execute($this->buildDto());
        } catch (ConfigWriteException) {
            return $this->renderForm(__('ERROR: Can not write file `system.local.php`'));
        }

        $_SESSION['success_message'] = __('Changes saved successfully');
        redirect(self::URL);
    }

    private function buildDto(): CookieBannerSettingsDTO
    {
        $languageCodes = array_keys(config('johncms')['lng_list'] ?? []);

        $postedTexts = $this->request->getPost('cookie_banner_text');
        $texts = [];
        if (is_array($postedTexts)) {
            foreach ($languageCodes as $code) {
                $texts[$code] = trim((string) ($postedTexts[$code] ?? ''));
            }
        }

        return new CookieBannerSettingsDTO(
            enabled: $this->request->getPost('cookie_banner_enabled') !== null ? 1 : 0,
            version: max(1, (int) $this->request->getPost('cookie_banner_version', 1, FILTER_VALIDATE_INT)),
            texts: $texts,
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
        $title = __('Cookie banner');
        $this->navChain->add($title);

        $successMessage = null;
        if (! empty($_SESSION['success_message'])) {
            $successMessage = (string) $_SESSION['success_message'];
            unset($_SESSION['success_message']);
        }

        $config = config('johncms');
        $languages = [];
        foreach ($config['lng_list'] ?? [] as $code => $data) {
            $languages[] = ['code' => $code, 'name' => $data['name'] ?? $code];
        }

        $this->render->addData([
            'title'       => $title,
            'page_title'  => $title,
            'module_menu' => ['cookie_banner' => true],
        ]);

        return $this->render->render('consent::admin/cookie-banner', [
            'form_action'     => self::URL,
            'languages'       => $languages,
            'enabled'         => ! empty($config['cookie_banner_enabled']),
            'version'         => (int) ($config['cookie_banner_version'] ?? 1),
            'texts'           => $config['cookie_banner_text'] ?? [],
            'error_message'   => $errorMessage,
            'success_message' => $successMessage,
        ]);
    }
}
