<?php

declare(strict_types=1);

namespace Johncms\Updates;

interface ModuleUpdateFinisherInterface
{
    public function finish(string $module): void;
}
