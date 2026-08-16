<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Infrastructure\Config;

use Johncms\Modules\Admin\Domain\Exceptions\ConfigWriteException;
use Johncms\Modules\Admin\Domain\Repository\AuthConfigRepositoryInterface;

final class FileSystemAuthConfigRepository implements AuthConfigRepositoryInterface
{
    private const FILE = 'autoload/auth.local.php';

    public function get(): array
    {
        return config('auth') ?? [];
    }

    public function save(array $auth): void
    {
        // Merged over what the local file already holds rather than over the merged configuration:
        // writing back everything config() returns would freeze today's defaults into the file,
        // and an upgrade changing one of them would then be ignored.
        $stored = $this->stored();
        $configFile = "<?php\n\n" . 'return ' . var_export(['auth' => $this->merge($stored, $auth)], true) . ";\n";

        if (file_put_contents(CONFIG_PATH . self::FILE, $configFile) === false) {
            throw new ConfigWriteException('Can not write file `auth.local.php`');
        }

        // The compiled container is built from configuration, so it has to go with it.
        $containerCache = CACHE_PATH . 'container.php';
        if (is_file($containerCache)) {
            unlink($containerCache);
        }

        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function stored(): array
    {
        $path = CONFIG_PATH . self::FILE;

        if (! is_file($path)) {
            return [];
        }

        /** @var array<string, mixed> $values */
        $values = (array) require $path;

        return (array) ($values['auth'] ?? []);
    }

    /**
     * @param array<string, mixed> $stored
     * @param array<string, mixed> $changes
     *
     * @return array<string, mixed>
     */
    private function merge(array $stored, array $changes): array
    {
        foreach ($changes as $key => $value) {
            $stored[$key] = is_array($value) && is_array($stored[$key] ?? null)
                ? $this->merge($stored[$key], $value)
                : $value;
        }

        return $stored;
    }
}
