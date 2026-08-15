<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Settings;

use Johncms\Http\Session;
use Johncms\Modules\Admin\Application\DTO\SystemSettingsDTO;
use Johncms\Modules\Admin\Application\UseCases\UpdateSystemSettingsUseCase;
use Johncms\Modules\Admin\Domain\Exceptions\ConfigWriteException;
use Johncms\Modules\Admin\Domain\Services\ThemeListProviderInterface;
use Johncms\Modules\Registration\Application\Services\RegistrationSettings;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;

final readonly class SystemSettingsController
{
    private const URL = '/admin/settings';

    public function __construct(
        private NavChain $navChain,
        private UpdateSystemSettingsUseCase $updateSystemSettingsUseCase,
        private ThemeListProviderInterface $themeListProvider,
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
            libraryComments: (bool) $request->bodyInt('mod_lib_comm'),
            downloadsComments: (bool) $request->bodyInt('mod_down_comm'),
            registrationModeration: (bool) $request->bodyInt('registration_moderation'),
        );
    }

    private function renderForm(string $errorMessage = ''): ViewResponse
    {
        $title = __('System Settings');
        $this->navChain->add($title);

        $timeShift = (int) config('johncms.timeshift', 0);

        return new ViewResponse(
            '@admin/settings.twig',
            [
                'title'           => $title,
                'page_title'      => $title,
                'sys_menu'        => ['settings' => true],
                'settings'        => $this->settings($timeShift),
                'themes'          => $this->themeListProvider->getAvailable(),
                'system_time'     => date('H:i', time() + $timeShift * 3600),
                'utc_time'        => date('H:i'),
                'form_action'     => self::URL,
                'error_message'   => $errorMessage,
                'success_message' => (string) $this->session->getFlash('success_message'),
            ]
        );
    }

    /**
     * The form reads a fixed set of values, so it gets that set and not the configuration of the
     * whole site.
     *
     * @return array<string, mixed>
     */
    private function settings(int $timeShift): array
    {
        return [
            'homeurl'                 => (string) config('johncms.homeurl', ''),
            'copyright'               => (string) config('johncms.copyright', ''),
            'email'                   => (string) config('johncms.email', ''),
            'flsz'                    => (int) config('johncms.flsz', 0),
            'gzip'                    => (bool) config('johncms.gzip', false),
            'user_email_required'     => (bool) config('johncms.user_email_required', false),
            'user_email_confirmation' => (bool) config('johncms.user_email_confirmation', false),
            'timeshift'               => $timeShift,
            'meta_title'              => (string) config('johncms.meta_title', ''),
            'meta_key'                => (string) config('johncms.meta_key', ''),
            'meta_desc'               => (string) config('johncms.meta_desc', ''),
            'privacy_policy_url'      => (string) config('johncms.privacy_policy_url', ''),
            'terms_of_use_url'        => (string) config('johncms.terms_of_use_url', ''),
            'personal_data_policy_url' => (string) config('johncms.personal_data_policy_url', ''),
            'cookie_policy_url'       => (string) config('johncms.cookie_policy_url', ''),
            'skindef'                 => (string) config('johncms.skindef', 'default'),
            'mod_lib_comm'            => (bool) config('johncms.mod_lib_comm', false),
            'mod_down_comm'           => (bool) config('johncms.mod_down_comm', false),
            'registration_moderation' => $this->registrationSettings->moderationEnabled(),
        ];
    }
}
