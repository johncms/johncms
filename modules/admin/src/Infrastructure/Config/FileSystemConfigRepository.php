<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Infrastructure\Config;

use Johncms\Modules\Admin\Domain\Exceptions\ConfigWriteException;
use Johncms\Modules\Admin\Domain\Repository\SystemConfigRepositoryInterface;

final class FileSystemConfigRepository implements SystemConfigRepositoryInterface
{
    public function getJohncms(): array
    {
        return config('johncms') ?? [];
    }

    public function saveJohncms(array $johncms): void
    {
        $configFile = "<?php\n\n" . 'return ' . var_export(['johncms' => $johncms], true) . ";\n";

        if (file_put_contents(CONFIG_PATH . 'autoload/system.local.php', $configFile) === false) {
            throw new ConfigWriteException('Can not write file `system.local.php`');
        }

        // The compiled container embeds config values (e.g. GuestbookAccess $config),
        // so the cache must be invalidated for the new settings to take effect.
        $containerCache = CACHE_PATH . 'container.php';
        if (is_file($containerCache)) {
            unlink($containerCache);
        }

        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
    }
}
