<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\ValueObjects;

/**
 * What the registry of the internet says about the network an address belongs to. Every field
 * but the range may be empty: registries fill in what their holders gave them.
 */
final readonly class IpRegistration
{
    public function __construct(
        public string $ip,
        public string $range,
        public string $handle,
        public string $name,
        public string $country,
        public string $organisation,
        public string $abuseEmail,
        public string $registeredAt,
        public string $updatedAt,
        /** The host of the registry that answered, e.g. rdap.db.ripe.net. */
        public string $registry,
        /** The full answer of the registry, for whoever needs more than the summary. */
        public string $sourceUrl,
    ) {
    }
}
