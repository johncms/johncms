<?php

declare(strict_types=1);

namespace Johncms\Checker;

interface DatabaseProbeInterface
{
    public function driver(): string;

    /** @return array{version_raw: string, version_clean: string, server_name: string, required_version: string, error: bool} */
    public function versionInfo(): array;

    public function checkMysqlnd(): bool;
}
