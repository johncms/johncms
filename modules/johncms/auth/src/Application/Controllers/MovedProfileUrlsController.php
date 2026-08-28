<?php

declare(strict_types=1);

namespace Johncms\Modules\Auth\Application\Controllers;

use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * The addresses these screens answered while they lived in the profile module.
 *
 * Kept because they are in e-mails already sent: a recovery link and an address-change link stay
 * valid for as long as their code does, and the message cannot be rewritten after the fact. They
 * live here rather than in the profile so that a site with profiles switched off still honours
 * the links it has sent.
 */
final readonly class MovedProfileUrlsController
{
    public function passwordRecovery(): RedirectResponse
    {
        return new RedirectResponse('/password-recovery', 301);
    }

    public function passwordRecoverySet(int $id, string $code): RedirectResponse
    {
        return new RedirectResponse('/password-recovery/' . $id . '/' . rawurlencode($code), 301);
    }

    public function confirmEmailChange(int $id, string $code): RedirectResponse
    {
        return new RedirectResponse('/confirm-email/' . $id . '/' . rawurlencode($code), 301);
    }
}
