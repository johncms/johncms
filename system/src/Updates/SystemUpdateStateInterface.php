<?php

declare(strict_types=1);

namespace Johncms\Updates;

interface SystemUpdateStateInterface
{
    public function isRequired(): bool;

    /**
     * Whether these modules still wait for their own update. The update of another module, or a
     * failure in it, says nothing about them.
     */
    public function isRequiredFor(string ...$modules): bool;

    public function begin(?string $module = null): void;
    public function complete(?string $module = null): void;
}
