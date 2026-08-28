<?php

declare(strict_types=1);

namespace Johncms\Auth;

/**
 * The settings of registration that are not about who may do it.
 *
 * Whether registration is open at all is a permission of the guest role; whether the accounts it
 * creates wait for an administrator is a workflow, and workflows stay settings. The old mod_reg
 * held both in one number, which is why 1 meant "open, with moderation" and 2 "open, without".
 *
 * In the core rather than in the module that draws the form: an account is also created by the
 * external sign-in flow, which runs on a site where the module is switched off, and the admin
 * panel edits the setting. All three have to read it the same way.
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
