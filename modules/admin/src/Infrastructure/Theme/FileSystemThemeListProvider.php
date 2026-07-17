<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Infrastructure\Theme;

use Johncms\Modules\Admin\Domain\Services\ThemeListProviderInterface;

final class FileSystemThemeListProvider implements ThemeListProviderInterface
{
    public function getAvailable(): array
    {
        $directories = glob(THEMES_PATH . '*', GLOB_ONLYDIR) ?: [];
        $themes = array_map('basename', $directories);

        return array_values(array_filter($themes, static fn (string $theme): bool => $theme !== 'admin'));
    }
}
