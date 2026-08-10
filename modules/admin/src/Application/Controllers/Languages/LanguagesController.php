<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Languages;

use Johncms\Modules\Admin\Application\UseCases\GetManagedLanguagesUseCase;
use Johncms\Modules\Admin\Application\UseCases\InstallLanguageUseCase;
use Johncms\Modules\Admin\Application\UseCases\RemoveLanguageUseCase;
use Johncms\Modules\Admin\Application\UseCases\SaveLanguageSettingsUseCase;
use Johncms\Modules\Admin\Domain\Exceptions\ConfigWriteException;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\View\ViewResponse;

final readonly class LanguagesController
{
    private const URL = '/admin/languages';
    private const MANAGE_URL = '/admin/languages/manage';

    public function __construct(
        private NavChain $navChain,
        private SaveLanguageSettingsUseCase $saveLanguageSettings,
        private GetManagedLanguagesUseCase $getManagedLanguages,
        private InstallLanguageUseCase $installLanguage,
        private RemoveLanguageUseCase $removeLanguage,
        private Session $session,
    ) {
    }

    public function index(): ViewResponse
    {
        return $this->renderIndex();
    }

    public function save(Request $request): ViewResponse
    {
        $defaultCode = $request->body('lng');
        $updateList = $request->hasBody('update');

        try {
            $this->saveLanguageSettings->execute(
                $defaultCode !== '' ? $defaultCode : null,
                $updateList
            );
        } catch (ConfigWriteException) {
            return $this->renderIndex(__('ERROR: Can not write file `system.local.php`'));
        }

        $this->session->flash('success_message', $updateList
            ? __('Descriptions have been updated successfully')
            : __('Settings are saved successfully'));
        redirect(self::URL);
    }

    public function manage(): ViewResponse
    {
        $title = __('Managing languages');
        $this->navChain->add(__('Languages'), self::URL);
        $this->navChain->add($title, self::MANAGE_URL);

        return new ViewResponse('@admin/languages-manage.twig', $this->menu($title) + [
            'message'    => (string) $this->session->getFlash('success_message'),
            'languages'  => $this->managedLanguages(),
            'manage_url' => self::MANAGE_URL,
        ]);
    }

    /**
     * The catalog is a remote list of arbitrary shape, and an entry of it carries no state of
     * the installation. The template gets every key it reads, whatever the source.
     *
     * @return list<array<string, mixed>>
     */
    private function managedLanguages(): array
    {
        $rows = [];
        foreach ($this->getManagedLanguages->execute() as $code => $language) {
            $rows[] = [
                'code'           => (string) $code,
                'name'           => (string) ($language['name'] ?? $code),
                'flag'           => (string) ($language['flag'] ?? ''),
                'version'        => (float) ($language['version'] ?? 0),
                'new_version'    => (float) ($language['new_version'] ?? 0),
                'installed'      => (bool) ($language['installed'] ?? false),
                'need_update'    => (bool) ($language['need_update'] ?? false),
                'access_problem' => (bool) ($language['access_problem'] ?? false),
            ];
        }

        return $rows;
    }

    public function install(Request $request): ViewResponse
    {
        return $this->runCatalogAction($request, __('The language was successfully installed'));
    }

    public function update(Request $request): ViewResponse
    {
        return $this->runCatalogAction($request, __('The language was successfully updated'));
    }

    public function delete(Request $request): ViewResponse
    {
        $code = $this->validatedCode($request);
        if ($code !== null) {
            try {
                $this->removeLanguage->execute($code);
                $this->session->flash('success_message', __('The language was successfully deleted'));
            } catch (ConfigWriteException) {
                $this->session->flash('success_message', __('ERROR: Can not write file `system.local.php`'));
            }
        }

        redirect(self::MANAGE_URL);
    }

    private function runCatalogAction(Request $request, string $successMessage): ViewResponse
    {
        $code = $this->validatedCode($request);
        if ($code !== null) {
            try {
                $this->installLanguage->execute($code);
                $this->session->flash('success_message', $successMessage);
            } catch (ConfigWriteException) {
                $this->session->flash('success_message', __('ERROR: Can not write file `system.local.php`'));
            }
        }

        redirect(self::MANAGE_URL);
    }

    private function validatedCode(Request $request): ?string
    {
        $code = trim($request->body('code', ''));

        return $code !== '' ? $code : null;
    }

    private function renderIndex(string $errorMessage = ''): ViewResponse
    {
        $title = __('Languages');
        $this->navChain->add($title, self::URL);

        return new ViewResponse('@admin/languages.twig', $this->menu($title) + [
            'languages'       => $this->installedLanguages(),
            'form_action'     => self::URL,
            'manage_url'      => self::MANAGE_URL,
            'error_message'   => $errorMessage,
            'success_message' => (string) $this->session->getFlash('success_message'),
        ]);
    }

    /**
     * @return list<array{code: string, name: string, active: bool}>
     */
    private function installedLanguages(): array
    {
        $current = (string) config('johncms.lng', '');

        $languages = [];
        foreach ((array) config('johncms.lng_list', []) as $code => $language) {
            $languages[] = [
                'code'   => (string) $code,
                'name'   => (string) ($language['name'] ?? $code),
                'active' => $code === $current,
            ];
        }

        return $languages;
    }

    /**
     * @return array<string, mixed>
     */
    private function menu(string $title): array
    {
        return [
            'title'      => $title,
            'page_title' => $title,
            'sys_menu'   => ['languages' => true],
        ];
    }
}
