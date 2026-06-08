<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Modules\Admin\Domain\Repository\SystemConfigRepositoryInterface;
use Johncms\Modules\Admin\Domain\Services\LanguageCatalogInterface;
use Johncms\Modules\Admin\Domain\Services\LanguageFilesManagerInterface;

final readonly class GetManagedLanguagesUseCase
{
    public function __construct(
        private SystemConfigRepositoryInterface $configRepository,
        private LanguageCatalogInterface $catalog,
        private LanguageFilesManagerInterface $filesManager,
    ) {
    }

    /**
     * Список языков для страницы управления: доступные из каталога + установленные
     * (с флагом, признаком обновления и проблемой доступа к файлам). Без `en`.
     *
     * @return array<string, array<string, mixed>>
     */
    public function execute(): array
    {
        $config = $this->configRepository->getJohncms();
        $available = $this->catalog->getAvailable();
        $installed = $config['lng_list'] ?? [];

        $languages = [];
        foreach ($installed as $code => $item) {
            $item['installed'] = true;
            $item['need_update'] = false;
            $item['new_version'] = '';
            $item['access_problem'] = $this->filesManager->hasAccessProblem($code);

            if (isset($available[$code]) && $available[$code]['version'] > $item['version']) {
                $item['need_update'] = true;
                $item['new_version'] = $available[$code]['version'];
            }

            $flag = THEMES_PATH . 'default/assets/images/flags/' . strtolower($code) . '.svg';
            if (is_file($flag)) {
                $item['flag'] = pathToUrl($flag);
            }

            $languages[$code] = $item;
        }

        $languages = array_merge($available, $languages);
        unset($languages['en']);

        return $languages;
    }
}
