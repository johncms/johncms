<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Modules\Admin\Domain\Exceptions\ConfigWriteException;
use Johncms\Modules\Admin\Domain\Repository\SystemConfigRepositoryInterface;
use Johncms\Modules\Admin\Domain\Services\LanguageFilesManagerInterface;

/**
 * Which languages the site offers and which of them is the default.
 *
 * The configured list (lng_list) holds the languages on offer only: the switch, the profile and the
 * detection of the language read it as they are. A language left out keeps its files, so turning
 * it on again needs nothing but this page.
 */
final readonly class SaveLanguageSettingsUseCase
{
    public function __construct(
        private SystemConfigRepositoryInterface $configRepository,
        private LanguageFilesManagerInterface $filesManager,
    ) {
    }

    /**
     * @param list<string> $enabled Codes of the languages on offer; the default is always among them.
     * @throws ConfigWriteException
     */
    public function execute(string $defaultCode, array $enabled): void
    {
        $installed = $this->filesManager->getInstalled();
        $config = $this->configRepository->getJohncms();

        if (isset($installed[$defaultCode])) {
            $config['lng'] = $defaultCode;
        }
        $default = (string) ($config['lng'] ?? '');

        $config['lng_list'] = array_filter(
            $installed,
            static fn(string $code): bool => $code === $default || in_array($code, $enabled, true),
            ARRAY_FILTER_USE_KEY
        );

        $this->configRepository->saveJohncms($config);
    }
}
