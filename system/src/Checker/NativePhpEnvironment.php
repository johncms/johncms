<?php

declare(strict_types=1);

namespace Johncms\Checker;

final readonly class NativePhpEnvironment implements PhpEnvironmentInterface
{
    public function version(): string
    {
        return PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.' . PHP_RELEASE_VERSION;
    }

    public function extensionLoaded(string $extension): bool
    {
        return extension_loaded($extension);
    }

    public function ini(string $name): string
    {
        $value = ini_get($name);

        return $value === false ? '' : $value;
    }

    public function sapi(): string
    {
        return PHP_SAPI;
    }
}
