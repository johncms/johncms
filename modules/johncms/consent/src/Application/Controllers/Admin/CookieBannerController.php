<?php

declare(strict_types=1);

namespace Johncms\Modules\Consent\Application\Controllers\Admin;

use Johncms\Modules\Admin\Application\Services\AdminLanguageOptions;
use Johncms\Modules\Admin\Domain\Exceptions\ConfigWriteException;
use Johncms\Modules\Consent\Application\DTO\CookieBannerSettingsDTO;
use Johncms\Modules\Consent\Application\Services\CookieBannerTextFormatter;
use Johncms\Modules\Consent\Application\UseCases\UpdateCookieBannerSettingsUseCase;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\Http\Session;
use Twig\Markup;

final readonly class CookieBannerController
{
    private const URL = '/admin/cookie-banner';

    public function __construct(
        private Session $session,
        private NavChain $navChain,
        private UpdateCookieBannerSettingsUseCase $updateCookieBannerSettings,
        private AdminLanguageOptions $languageOptions,
        private CookieBannerTextFormatter $formatter,
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
            return $this->renderForm(__('ERROR: Can not write file `system.local.php`'), $request->body('cookie_banner_language'));
        }

        $this->session->flash('success_message', $request->hasBody('cookie_banner_reshow')
            ? __('Changes saved. The banner is shown to every visitor again.')
            : __('Changes saved successfully'));
        redirect(self::URL);
    }

    private function buildDto(Request $request): CookieBannerSettingsDTO
    {
        $postedTexts = $request->bodyList('cookie_banner_text');
        $texts = [];
        foreach (array_column($this->languageOptions->languages(), 'code') as $code) {
            $texts[$code] = trim((string) ($postedTexts[$code] ?? ''));
        }

        return new CookieBannerSettingsDTO(
            enabled: $request->hasBody('cookie_banner_enabled') ? 1 : 0,
            texts: $texts,
            reshow: $request->hasBody('cookie_banner_reshow'),
        );
    }

    private function renderForm(?string $errorMessage = null, string $activeLanguage = ''): ViewResponse
    {
        $title = __('Cookie banner');
        $this->navChain->add($title);

        $config = config('johncms');
        $texts = (array) ($config['cookie_banner_text'] ?? []);
        $languages = $this->languageOptions->languages();
        $default = $this->languageOptions->defaultLanguage()['code'];

        // What a visitor of each language sees now, as the site picks it.
        $previews = [];
        foreach ($languages as $language) {
            $text = $this->formatter->pick($texts, [$language['code'], $default, 'en']);
            $previews[$language['code']] = $text === '' ? null : new Markup($this->formatter->toHtml($text), 'UTF-8');
        }

        return new ViewResponse('@consent/admin/cookie-banner.twig', [
            'title'       => $title,
            'page_title'  => $title,
            'module_menu' => ['cookie_banner' => true],
        ] + [
            'form_action'      => self::URL,
            'languages'        => $languages,
            'active_language'  => $this->languageOptions->active($activeLanguage),
            'default_language' => $this->languageOptions->defaultLanguage(),
            'languages_url'    => AdminLanguageOptions::LANGUAGES_URL,
            'enabled'          => ! empty($config['cookie_banner_enabled']),
            'version'          => $this->currentVersion(),
            'texts'            => $texts,
            'previews'         => $previews,
            'error_message'    => $errorMessage,
            'success_message'  => $this->session->getFlash('success_message'),
        ]);
    }

    private function currentVersion(): int
    {
        return max(1, (int) (config('johncms')['cookie_banner_version'] ?? 1));
    }
}
