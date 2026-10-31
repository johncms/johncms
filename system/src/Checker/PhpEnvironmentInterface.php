<?php

declare(strict_types=1);

namespace Johncms\Checker;

interface PhpEnvironmentInterface
{
    public function version(): string;

    public function extensionLoaded(string $extension): bool;

    public function ini(string $name): string;

    public function sapi(): string;
}
