<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Modules\Admin\Domain\Exceptions\IpRegistryUnavailableException;
use Johncms\Modules\Admin\Domain\Services\IpRegistryInterface;
use Johncms\Modules\Admin\Domain\ValueObjects\IpRegistration;

final readonly class GetIpWhoisUseCase
{
    public function __construct(
        private IpRegistryInterface $registry,
    ) {
    }

    /**
     * Whether the registries can be asked about an address at all: a private, loopback or
     * reserved one belongs to no network of theirs.
     */
    public function isPublic(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }

    /**
     * @throws IpRegistryUnavailableException
     */
    public function execute(string $ip): ?IpRegistration
    {
        return $this->registry->lookup($ip);
    }
}
