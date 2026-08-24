<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Infrastructure\Config;

use Johncms\Modules\Admin\Domain\Exceptions\ConfigWriteException;

/**
 * Writing a section of the configuration into its `*.local.php` file.
 *
 * The two rules of doing it right were written twice before this existed, and getting either
 * wrong is silent: changes are merged over what the local file already holds rather than over the
 * merged configuration — writing back everything config() returns would freeze today's defaults
 * into the file, and an upgrade changing one of them would then be ignored — and the compiled
 * container has to go, because it is built from configuration and would otherwise keep answering
 * with the old values.
 */
final class LocalConfigWriter
{
    /**
     * @param array<string, mixed> $changes The keys to set, in the shape they have under $section.
     *
     * @throws ConfigWriteException
     */
    public function write(string $file, string $section, array $changes): void
    {
        $values = [$section => $this->merge($this->stored($file, $section), $changes)];
        $contents = "<?php\n\n" . 'return ' . var_export($values, true) . ";\n";

        if (file_put_contents(CONFIG_PATH . 'autoload/' . $file, $contents) === false) {
            throw new ConfigWriteException(sprintf('Can not write file `%s`', $file));
        }

        $containerCache = CACHE_PATH . 'container.php';
        if (is_file($containerCache)) {
            unlink($containerCache);
        }

        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
    }

    /**
     * What the local file itself holds — not what the section looks like after the global files
     * were merged into it.
     *
     * @return array<string, mixed>
     */
    public function stored(string $file, string $section): array
    {
        $path = CONFIG_PATH . 'autoload/' . $file;

        if (! is_file($path)) {
            return [];
        }

        /** @var array<string, mixed> $values */
        $values = (array) require $path;

        return (array) ($values[$section] ?? []);
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
