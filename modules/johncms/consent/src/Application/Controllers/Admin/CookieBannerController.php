<?php

declare(strict_types=1);

namespace Johncms\Modules\Consent\Application\Controllers\Admin;

use Johncms\Modules\Admin\Domain\Exceptions\ConfigWriteException;
use Johncms\Modules\Consent\Application\DTO\CookieBannerSettingsDTO;
use Johncms\Modules\Consent\Application\UseCases\UpdateCookieBannerSettingsUseCase;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\Http\Session;

final readonly class CookieBannerController
{
    private const URL = '/admin/cookie-banner';

    public function __construct(
        private Session $session,
        private NavChain $navChain,
        private UpdateCookieBannerSettingsUseCase $updateCookieBannerSettings,
    ) {
    }

    public function form(): ViewResponse
    {
        return $this->renderForm();
    }

    public function save(Request $request): ViewResponse
    {
        try {
            $this->updateCookieBannerSettings->execute($this->buildDto($request));
        } catch (ConfigWriteException) {
            return $this->renderForm(__('ERROR: Can not write file `system.local.php`'));
        }

        $this->session->flash('success_message', __('Changes saved successfully'));
        redirect(self::URL);
    }

    private function buildDto(Request $request): CookieBannerSettingsDTO
    {
        $languageCodes = array_keys(config('johncms')['lng_list'] ?? []);

        $postedTexts = $request->bodyList('cookie_banner_text');
        $texts = [];
        foreach ($languageCodes as $code) {
            $texts[$code] = trim((string) ($postedTexts[$code] ?? ''));
        }

        return new CookieBannerSettingsDTO(
            enabled: $request->hasBody('cookie_banner_enabled') ? 1 : 0,
            version: max(1, $request->bodyInt('cookie_banner_version', 1)),
            texts: $texts,
        );
    }

    private function renderForm(?string $errorMessage = null): ViewResponse
    {
        $title = __('Cookie banner');
        $this->navChain->add($title);

        $successMessage = $this->session->getFlash('success_message');

        $config = config('johncms');
        $languages = [];
        foreach ($config['lng_list'] ?? [] as $code => $data) {
            $languages[] = ['code' => $code, 'name' => $data['name'] ?? $code];
        }

        return new ViewResponse('@consent/admin/cookie-banner.twig', [
            'title'       => $title,
            'page_title'  => $title,
            'module_menu' => ['cookie_banner' => true],
        ] + [
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
