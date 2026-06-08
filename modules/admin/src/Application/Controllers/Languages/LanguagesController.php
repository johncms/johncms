<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Languages;

use Johncms\Http\Controller\AdminControllerContext;
use Johncms\Modules\Admin\Application\UseCases\GetManagedLanguagesUseCase;
use Johncms\Modules\Admin\Application\UseCases\InstallLanguageUseCase;
use Johncms\Modules\Admin\Application\UseCases\RemoveLanguageUseCase;
use Johncms\Modules\Admin\Application\UseCases\SaveLanguageSettingsUseCase;
use Johncms\Modules\Admin\Domain\Exceptions\ConfigWriteException;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;
use Johncms\Validator\Validator;

final readonly class LanguagesController
{
    private const URL = '/admin/languages';
    private const MANAGE_URL = '/admin/languages/manage';

    public function __construct(
        private AdminControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private SaveLanguageSettingsUseCase $saveLanguageSettings,
        private GetManagedLanguagesUseCase $getManagedLanguages,
        private InstallLanguageUseCase $installLanguage,
        private RemoveLanguageUseCase $removeLanguage,
    ) {
        $this->controllerContext->initModule('admin');
    }

    public function index(): string
    {
        return $this->renderIndex();
    }

    public function save(): string
    {
        if (! $this->isCsrfValid()) {
            return $this->renderIndex(__('Wrong data'));
        }

        $defaultCode = $this->request->getPost('lng');
        $updateList = $this->request->getPost('update') !== null;

        try {
            $this->saveLanguageSettings->execute(
                $defaultCode !== null ? trim((string) $defaultCode) : null,
                $updateList
            );
        } catch (ConfigWriteException) {
            return $this->renderIndex(__('ERROR: Can not write file `system.local.php`'));
        }

        $_SESSION['success_message'] = $updateList
            ? __('Descriptions have been updated successfully')
            : __('Settings are saved successfully');
        redirect(self::URL);
    }

    public function manage(): string
    {
        $title = __('Managing languages');
        $this->navChain->add(__('Languages'), self::URL);
        $this->navChain->add($title, self::MANAGE_URL);

        $message = null;
        if (! empty($_SESSION['success_message'])) {
            $message = (string) $_SESSION['success_message'];
            unset($_SESSION['success_message']);
        }

        $this->render->addData(
            [
                'title'      => $title,
                'page_title' => $title,
                'sys_menu'   => ['languages' => true],
            ]
        );

        return $this->render->render(
            'admin::languages/manage',
            [
                'message'     => $message,
                'languages'   => $this->getManagedLanguages->execute(),
                'manage_url'  => self::MANAGE_URL,
            ]
        );
    }

    public function install(): string
    {
        return $this->runCatalogAction(__('The language was successfully installed'));
    }

    public function update(): string
    {
        return $this->runCatalogAction(__('The language was successfully updated'));
    }

    public function delete(): string
    {
        $code = $this->validatedCode();
        if ($code !== null) {
            try {
                $this->removeLanguage->execute($code);
                $_SESSION['success_message'] = __('The language was successfully deleted');
            } catch (ConfigWriteException) {
                $_SESSION['success_message'] = __('ERROR: Can not write file `system.local.php`');
            }
        }

        redirect(self::MANAGE_URL);
    }

    private function runCatalogAction(string $successMessage): string
    {
        $code = $this->validatedCode();
        if ($code !== null) {
            try {
                $this->installLanguage->execute($code);
                $_SESSION['success_message'] = $successMessage;
            } catch (ConfigWriteException) {
                $_SESSION['success_message'] = __('ERROR: Can not write file `system.local.php`');
            }
        }

        redirect(self::MANAGE_URL);
    }

    private function validatedCode(): ?string
    {
        if (! $this->isCsrfValid()) {
            return null;
        }

        $code = trim((string) $this->request->getPost('code', ''));

        return $code !== '' ? $code : null;
    }

    private function isCsrfValid(): bool
    {
        $validator = new Validator(
            ['csrf_token' => (string) $this->request->getPost('csrf_token', '')],
            ['csrf_token' => ['Csrf']]
        );

        return $validator->isValid();
    }

    private function renderIndex(?string $errorMessage = null): string
    {
        $title = __('Languages');
        $this->navChain->add($title, self::URL);

        $successMessage = null;
        if (! empty($_SESSION['success_message'])) {
            $successMessage = (string) $_SESSION['success_message'];
            unset($_SESSION['success_message']);
        }

        $this->render->addData(
            [
                'title'      => $title,
                'page_title' => $title,
                'sys_menu'   => ['languages' => true],
            ]
        );

        return $this->render->render(
            'admin::languages/index',
            [
                'config'          => config('johncms'),
                'form_action'     => self::URL,
                'manage_url'      => self::MANAGE_URL,
                'error_message'   => $errorMessage,
                'success_message' => $successMessage,
            ]
        );
    }
}
