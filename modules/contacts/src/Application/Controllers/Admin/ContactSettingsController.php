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
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\Http\Session;
use Johncms\Validator\Validator;

final readonly class ContactSettingsController
{
    private const URL = '/admin/contacts';

    public function __construct(
        private AdminControllerContext $controllerContext,
        private Session $session,
        private NavChain $navChain,
        private ContactSettingsProvider $settingsProvider,
        private UpdateContactSettingsUseCase $updateSettings,
    ) {
        $this->controllerContext->initModule('contacts');
    }

    public function form(): ViewResponse
    {
        return $this->renderForm($this->settingsProvider->getSettings());
    }

    public function save(Request $request): ViewResponse
    {
        $settings = $this->buildDto($request);

        if (! $this->isCsrfValid($request)) {
            return $this->renderForm($settings, __('Wrong data'));
        }

        try {
            $this->updateSettings->execute($settings);
        } catch (ConfigWriteException) {
            return $this->renderForm($settings, __('ERROR: Can not write file `system.local.php`'));
        }

        $this->session->flash('success_message', __('Changes saved successfully'));
        redirect(self::URL);
    }

    private function buildDto(Request $request): ContactSettingsDTO
    {
        return new ContactSettingsDTO(
            formEnabled: $request->hasBody('contacts_form_enabled'),
            notifyEmail: trim($request->body('contacts_notify_email', '')),
            email: trim($request->body('contacts_email', '')),
            phone: trim($request->body('contacts_phone', '')),
            socials: $this->postedSocials($request),
            addresses: $this->postedTranslations($request, 'contacts_address'),
            workingHours: $this->postedTranslations($request, 'contacts_working_hours'),
            texts: $this->postedTranslations($request, 'contacts_text'),
        );
    }

    /**
     * Social links are edited as one link per line in the `Title|URL` format.
     *
     * @return list<SocialLinkDTO>
     */
    private function postedSocials(Request $request): array
    {
        $lines = preg_split('/\R/', $request->body('contacts_socials', '')) ?: [];

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
    private function postedTranslations(Request $request, string $field): array
    {
        $posted = $request->bodyList($field);

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

    private function isCsrfValid(Request $request): bool
    {
        $validator = new Validator(
            ['csrf_token' => $request->body('csrf_token', '')],
            ['csrf_token' => ['Csrf']]
        );

        return $validator->isValid();
    }

    private function renderForm(ContactSettingsDTO $settings, ?string $errorMessage = null): ViewResponse
    {
        $title = __('Contacts');
        $this->navChain->add($title, self::URL);

        $successMessage = $this->session->getFlash('success_message');

        $languages = [];
        foreach (config('johncms')['lng_list'] ?? [] as $code => $data) {
            $languages[] = ['code' => $code, 'name' => $data['name'] ?? $code];
        }

        $socials = implode(
            "\n",
            array_map(static fn(SocialLinkDTO $link): string => $link->title . '|' . $link->url, $settings->socials)
        );


        return new ViewResponse(
            '@contacts/admin/settings.twig',
            [
                'title'       => $title,
                'page_title'  => $title,
                'module_menu' => ['contacts' => true],
            ] + [
                'form_action'     => self::URL,
                'messages_url'    => self::URL . '/messages',
                'languages'       => $languages,
                'settings'        => $settings,
                'multilingual_fields' => [
                    ['name' => 'contacts_address', 'label' => __('Address'), 'values' => $settings->addresses],
                    ['name' => 'contacts_working_hours', 'label' => __('Working hours'), 'values' => $settings->workingHours],
                    ['name' => 'contacts_text', 'label' => __('Text above the contact information'), 'values' => $settings->texts],
                ],
                'socials'         => $socials,
                'error_message'   => $errorMessage,
                'success_message' => $successMessage,
            ]
        );
    }
}
