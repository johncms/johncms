<?php

declare(strict_types=1);

namespace Johncms\Modules\Contacts\Application\Controllers\Admin;

use Johncms\Http\Controller\AdminControllerContext;
use Johncms\Modules\Admin\Domain\Exceptions\ConfigWriteException;
use Johncms\Modules\Contacts\Application\DTO\ContactSettingsDTO;
use Johncms\Modules\Contacts\Application\DTO\SocialLinkDTO;
use Johncms\Modules\Contacts\Application\Services\ContactSettingsProvider;
use Johncms\Modules\Contacts\Application\UseCases\UpdateContactSettingsUseCase;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;
use Johncms\Validator\Validator;

final readonly class ContactSettingsController
{
    private const URL = '/admin/contacts';

    public function __construct(
        private AdminControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private ContactSettingsProvider $settingsProvider,
        private UpdateContactSettingsUseCase $updateSettings,
    ) {
        $this->controllerContext->initModule('contacts');
    }

    public function form(): string
    {
        return $this->renderForm($this->settingsProvider->getSettings());
    }

    public function save(): string
    {
        $settings = $this->buildDto();

        if (! $this->isCsrfValid()) {
            return $this->renderForm($settings, __('Wrong data'));
        }

        try {
            $this->updateSettings->execute($settings);
        } catch (ConfigWriteException) {
            return $this->renderForm($settings, __('ERROR: Can not write file `system.local.php`'));
        }

        $_SESSION['success_message'] = __('Changes saved successfully');
        redirect(self::URL);
    }

    private function buildDto(): ContactSettingsDTO
    {
        return new ContactSettingsDTO(
            formEnabled: $this->request->getPost('contacts_form_enabled') !== null,
            notifyEmail: trim((string) $this->request->getPost('contacts_notify_email', '')),
            email: trim((string) $this->request->getPost('contacts_email', '')),
            phone: trim((string) $this->request->getPost('contacts_phone', '')),
            socials: $this->postedSocials(),
            addresses: $this->postedTranslations('contacts_address'),
            workingHours: $this->postedTranslations('contacts_working_hours'),
            texts: $this->postedTranslations('contacts_text'),
        );
    }

    /**
     * Social links are edited as one link per line in the `Title|URL` format.
     *
     * @return list<SocialLinkDTO>
     */
    private function postedSocials(): array
    {
        $lines = preg_split('/\R/', (string) $this->request->getPost('contacts_socials', '')) ?: [];

        $links = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || ! str_contains($line, '|')) {
                continue;
            }

            [$title, $url] = explode('|', $line, 2);
            $title = trim($title);
            $url = trim($url);
            if ($title === '' || $url === '') {
                continue;
            }

            $links[] = new SocialLinkDTO($title, $url);
        }

        return $links;
    }

    /**
     * @return array<string, string>
     */
    private function postedTranslations(string $field): array
    {
        $posted = $this->request->getPost($field);
        if (! is_array($posted)) {
            return [];
        }

        $values = [];
        foreach ($this->languageCodes() as $code) {
            $values[$code] = trim((string) ($posted[$code] ?? ''));
        }

        return $values;
    }

    /**
     * @return list<string>
     */
    private function languageCodes(): array
    {
        return array_keys(config('johncms')['lng_list'] ?? []);
    }

    private function isCsrfValid(): bool
    {
        $validator = new Validator(
            ['csrf_token' => (string) $this->request->getPost('csrf_token', '')],
            ['csrf_token' => ['Csrf']]
        );

        return $validator->isValid();
    }

    private function renderForm(ContactSettingsDTO $settings, ?string $errorMessage = null): string
    {
        $title = __('Contacts');
        $this->navChain->add($title, self::URL);

        $successMessage = null;
        if (! empty($_SESSION['success_message'])) {
            $successMessage = (string) $_SESSION['success_message'];
            unset($_SESSION['success_message']);
        }

        $languages = [];
        foreach (config('johncms')['lng_list'] ?? [] as $code => $data) {
            $languages[] = ['code' => $code, 'name' => $data['name'] ?? $code];
        }

        $socials = implode(
            "\n",
            array_map(static fn(SocialLinkDTO $link): string => $link->title . '|' . $link->url, $settings->socials)
        );

        $this->render->addData(
            [
                'title'       => $title,
                'page_title'  => $title,
                'module_menu' => ['contacts' => true],
            ]
        );

        return $this->render->render(
            'contacts::admin/settings',
            [
                'form_action'     => self::URL,
                'messages_url'    => self::URL . '/messages',
                'languages'       => $languages,
                'settings'        => $settings,
                'socials'         => $socials,
                'error_message'   => $errorMessage,
                'success_message' => $successMessage,
            ]
        );
    }
}
