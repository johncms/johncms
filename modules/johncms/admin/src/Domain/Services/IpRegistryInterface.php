<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Services;

use Johncms\Modules\Admin\Domain\Exceptions\IpRegistryUnavailableException;
use Johncms\Modules\Admin\Domain\ValueObjects\IpRegistration;

interface IpRegistryInterface
{
    /**
     * The network a public address belongs to; null when the registries know nothing of it.
     *
     * @throws IpRegistryUnavailableException
     */
    public function lookup(string $ip): ?IpRegistration;
}
