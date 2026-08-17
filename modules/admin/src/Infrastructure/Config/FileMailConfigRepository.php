<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Infrastructure\Config;

use Johncms\Modules\Admin\Domain\Exceptions\ConfigWriteException;
use Johncms\Modules\Admin\Domain\Repository\MailConfigRepositoryInterface;

final class FileMailConfigRepository implements MailConfigRepositoryInterface
{
    public function getMail(): array
    {
        return config('mail') ?? [];
    }

    public function saveMail(array $mail): void
    {
        $configFile = "<?php\n\n" . 'return ' . var_export(['mail' => $mail], true) . ";\n";

        if (file_put_contents(CONFIG_PATH . 'autoload/mail.local.php', $configFile) === false) {
            throw new ConfigWriteException('Can not write file `mail.local.php`');
        }

        // The mailer is built from this configuration while the container is compiled, so the
        // compiled container has to go for the new settings to take effect.
        $containerCache = CACHE_PATH . 'container.php';
        if (is_file($containerCache)) {
            unlink($containerCache);
        }

        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
    }
}
