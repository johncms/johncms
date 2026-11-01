<?php

declare(strict_types=1);

namespace Johncms\Updates;

interface SystemUpdateStateInterface
{
    public function isRequired(): bool;
    public function begin(?string $module = null): void;
    public function complete(?string $module = null): void;
}
