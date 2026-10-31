<?php

declare(strict_types=1);

namespace Johncms\Checker;

use Johncms\Cache\CacheInterface;
use RuntimeException;

final readonly class CacheRoundTripProbe implements CacheProbeInterface
{
    public function __construct(private CacheInterface $cache)
    {
    }

    public function roundTrip(): bool
    {
        $key = 'system_check_' . bin2hex(random_bytes(16));
        $value = bin2hex(random_bytes(16));
        try {
            return $this->cache->set($key, $value, 60) && $this->cache->get($key) === $value;
        } finally {
            if (! $this->cache->delete($key)) {
                throw new RuntimeException('Could not remove the temporary system check cache entry.');
            }
        }
    }
}
