<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Modules\Manifest;

use Johncms\Modules\Exceptions\InvalidModuleManifestException;

/**
 * Reads module.php and answers with a manifest, or refuses with a message saying what is wrong
 * with the file.
 *
 * The manifest is a PHP array rather than JSON, the same as a theme manifest: the file is
 * required, not parsed, and nothing of the module is loaded to read it.
 */
final readonly class ModuleManifestLoader
{
    public const string MANIFEST = 'module.php';

    /** `vendor/name`, the form of a Composer package name. */
    private const string KEY_PATTERN = '/^[a-z0-9][a-z0-9_-]*\/[a-z0-9][a-z0-9_-]*$/';

    /** Flat: a Twig namespace ends at the first slash, and a dictionary is a file name. */
    private const string ALIAS_PATTERN = '/^[a-z0-9][a-z0-9_.-]*$/';

    /**
     * @param string $directory The module directory, the one holding module.php.
     * @throws InvalidModuleManifestException
     */
    public function load(string $directory): ModuleManifest
    {
        $directory = rtrim($directory, DIRECTORY_SEPARATOR);
        $file = $directory . DIRECTORY_SEPARATOR . self::MANIFEST;

        if (! is_file($file)) {
            throw new InvalidModuleManifestException(sprintf('The module in "%s" has no %s.', $directory, self::MANIFEST));
        }

        /** @psalm-suppress UnresolvableInclude */
        $manifest = require $file;

        if (! is_array($manifest)) {
            throw new InvalidModuleManifestException(sprintf('"%s" must return an array.', $file));
        }

        $key = $this->key($manifest, $file, $directory);
        $name = basename($key);

        return new ModuleManifest(
            key: $key,
            alias: $this->alias($manifest, $file, $key),
            path: $directory,
            name: $this->string($manifest, 'name', $file) ?? ucfirst($name),
            version: $this->string($manifest, 'version', $file),
            system: $this->bool($manifest, 'system', $file),
        );
    }

    /**
     * The key has to name the place the file lies in. Both are the identity of the module, and a
     * module answering to a key other than its own directory would be found by the registry and
     * missed by everything resolving a path — the classic half-installed module.
     *
     * @param array<mixed> $manifest
     */
    private function key(array $manifest, string $file, string $directory): string
    {
        $key = $this->string($manifest, 'key', $file);

        if ($key === null) {
            throw new InvalidModuleManifestException(sprintf('"%s" declares no key.', $file));
        }

        if (preg_match(self::KEY_PATTERN, $key) !== 1) {
            throw new InvalidModuleManifestException(
                sprintf('"%s" declares the key "%s", which is not of the form vendor/name.', $file, $key)
            );
        }

        $expected = basename(dirname($directory)) . '/' . basename($directory);
        if ($key !== $expected) {
            throw new InvalidModuleManifestException(
                sprintf('"%s" declares the key "%s" but lies in "%s".', $file, $key, $expected)
            );
        }

        return $key;
    }

    /**
     * Absent, the alias is the key with the slash replaced: `vasya/blog` becomes `vasya.blog`.
     * Long, but always free — which a module declaring a short alias of its own is not, and that
     * is for the registry to check when it is installed.
     *
     * @param array<mixed> $manifest
     */
    private function alias(array $manifest, string $file, string $key): string
    {
        $alias = $this->string($manifest, 'alias', $file);

        if ($alias === null) {
            return str_replace('/', '.', $key);
        }

        if (preg_match(self::ALIAS_PATTERN, $alias) !== 1) {
            throw new InvalidModuleManifestException(
                sprintf(
                    '"%s" declares the alias "%s". An alias is a Twig namespace and the name of a dictionary,'
                    . ' so it may only hold lowercase letters, digits, dots, hyphens and underscores.',
                    $file,
                    $alias
                )
            );
        }

        return $alias;
    }

    /**
     * @param array<mixed> $manifest
     */
    private function string(array $manifest, string $field, string $file): ?string
    {
        $value = $manifest[$field] ?? null;

        if ($value === null || $value === '') {
            return null;
        }

        if (! is_string($value)) {
            throw new InvalidModuleManifestException(
                sprintf('"%s": the "%s" field must be a string.', $file, $field)
            );
        }

        return $value;
    }

    /**
     * @param array<mixed> $manifest
     */
    private function bool(array $manifest, string $field, string $file): bool
    {
        $value = $manifest[$field] ?? false;

        if (! is_bool($value)) {
            throw new InvalidModuleManifestException(
                sprintf('"%s": the "%s" field must be true or false.', $file, $field)
            );
        }

        return $value;
    }
}
