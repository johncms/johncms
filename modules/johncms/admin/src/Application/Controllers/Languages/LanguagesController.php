<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Languages;

use Johncms\Modules\Admin\Application\UseCases\SaveLanguageSettingsUseCase;
use Johncms\Modules\Admin\Domain\Exceptions\ConfigWriteException;
use Johncms\Modules\Admin\Domain\Services\LanguageFilesManagerInterface;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\View\ViewResponse;

final readonly class LanguagesController
{
    private const URL = '/admin/languages';

    public function __construct(
        private NavChain $navChain,
        private SaveLanguageSettingsUseCase $saveLanguageSettings,
        private LanguageFilesManagerInterface $filesManager,
        private Session $session,
    ) {
    }

    public function index(): ViewResponse
    {
        return $this->renderIndex();
    }

    public function save(Request $request): ViewResponse
    {
        try {
            $this->saveLanguageSettings->execute(
                $request->body('lng'),
                array_values(array_map('strval', $request->bodyList('enabled')))
            );
        } catch (ConfigWriteException) {
            return $this->renderIndex(__('ERROR: Can not write file `system.local.php`'));
        }

        $this->session->flash('success_message', __('Settings are saved successfully'));
        redirect(self::URL);
    }

    private function renderIndex(string $errorMessage = ''): ViewResponse
    {
        $title = __('Languages');
        $this->navChain->add($title, self::URL);

        return new ViewResponse('@admin/languages.twig', [
            'title'           => $title,
            'page_title'      => $title,
            'sys_menu'        => ['languages' => true],
            'languages'       => $this->languages(),
            'form_action'     => self::URL,
            'error_message'   => $errorMessage,
            'success_message' => (string) $this->session->getFlash('success_message'),
        ]);
    }

    /**
     * Every language whose files are on the disk, read afresh: one that came with an update of
     * the site shows up here, turned off until it is turned on.
     *
     * @return list<array{code: string, name: string, default: bool, enabled: bool}>
     */
    private function languages(): array
    {
        $default = (string) config('johncms.lng', '');
        $enabled = (array) config('johncms.lng_list', []);

        $languages = [];
        foreach ($this->filesManager->getInstalled() as $code => $language) {
            $code = (string) $code;
            $languages[] = [
                'code'    => $code,
                'name'    => $language['name'],
                'default' => $code === $default,
                'enabled' => $code === $default || isset($enabled[$code]),
            ];
        }

        return $languages;
    }
}
