<?php

declare(strict_types=1);

namespace Johncms\Checker;

interface CacheProbeInterface
{
    public function roundTrip(): bool;
}
