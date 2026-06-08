<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Modules\Admin\Domain\Exceptions\ConfigWriteException;
use Johncms\Modules\Admin\Domain\Services\LanguageCatalogInterface;

final readonly class InstallLanguageUseCase
{
    public function __construct(
        private LanguageCatalogInterface $catalog,
        private UpdateLanguagesListUseCase $updateLanguagesList,
    ) {
    }

    /**
     * @throws ConfigWriteException
     */
    public function execute(string $code): void
    {
        $this->catalog->install($code);
        $this->updateLanguagesList->execute();
    }
}
