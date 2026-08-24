<?php

declare(strict_types=1);

namespace Johncms\Modules\Registration\Application\Services;

/**
 * The settings of registration that are not about who may do it.
 *
 * Whether registration is open at all is a permission of the guest role; whether the accounts it
 * creates wait for an administrator is a workflow, and workflows stay settings. The old mod_reg
 * held both in one number, which is why 1 meant "open, with moderation" and 2 "open, without".
 */
final readonly class RegistrationSettings
{
    public function moderationEnabled(): bool
    {
        $config = config('johncms');

        // The new key is null until the site saves its settings once. Until then the old number
        // is what says whether registrations were moderated, and reading it here is what keeps a
        // site that updated yesterday from letting everybody in unchecked.
        return (bool) ($config['registration_moderation'] ?? ((int) ($config['mod_reg'] ?? 0) === 1));
    }
}
