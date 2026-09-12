<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Infrastructure\Config;

use Johncms\Modules\Admin\Domain\Exceptions\ConfigWriteException;
use Johncms\Modules\Forum\Domain\Repository\ForumConfigRepositoryInterface;

final class FileSystemForumConfigRepository implements ForumConfigRepositoryInterface
{
    public function getSettings(): array
    {
        return config('forum')['settings'] ?? [];
    }

    public function saveSettings(array $settings): void
    {
        $config = config('forum') ?? [];
        $config['settings'] = $settings;

        $configFile = "<?php\n\n" . 'return ' . var_export(['forum' => $config], true) . ";\n";

        if (file_put_contents(CONFIG_PATH . 'autoload/forum.local.php', $configFile) === false) {
            throw new ConfigWriteException('Can not write file `forum.local.php`');
        }

        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
    }
}
