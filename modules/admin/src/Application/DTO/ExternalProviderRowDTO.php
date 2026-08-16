<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\DTO;

/**
 * One sign-in service on the settings screen.
 */
final readonly class ExternalProviderRowDTO
{
    /**
     * @param bool $known              Whether a class still answers for this key. False for a row
     *                                 left behind by a module that was removed.
     * @param int  $users              How many accounts sign in through it.
     * @param int  $withoutAlternative How many of them have no other way in — the number that
     *                                 makes switching it off a decision rather than a click.
     */
    public function __construct(
        public string $key,
        public string $label,
        public bool $enabled,
        public string $clientId,
        public bool $hasSecret,
        public string $callbackUrl,
        public bool $known = true,
        public int $users = 0,
        public int $withoutAlternative = 0,
    ) {
    }
}
