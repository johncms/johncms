<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth;

use Johncms\Auth\Authentication\AuthenticatorChain;
use Johncms\Auth\Authorization\PermissionResolver;
use Johncms\Http\Request;
use Johncms\Users\Repository\UserRepositoryInterface;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Service\ResetInterface;

/**
 * The identity of the request being served, resolved once and shared for the rest of the cycle.
 *
 * Lazy on purpose: a page that never asks who the visitor is pays for no authentication. The
 * resolved identity is cached for the request and dropped by reset() before the next one, which
 * is what keeps one visitor's identity out of the next answer in a long-running runtime.
 *
 * There is no setter. Replacing the identity is done by registering an authenticator — that is
 * the extension point, and it is also how tests sign in as somebody, so production code needs
 * no seam for them.
 */
final class CurrentUser implements ResetInterface
{
    private ?Identity $identity = null;

    private ?User $user = null;

    public function __construct(
        private readonly AuthenticatorChain $authenticators,
        private readonly PermissionResolver $permissions,
        private readonly RequestStack $requestStack,
        private readonly UserRepositoryInterface $users,
    ) {
    }

    public function identity(): Identity
    {
        if ($this->identity === null) {
            $this->identity = $this->resolve();
        }

        return $this->identity;
    }

    public function id(): int
    {
        return $this->identity()->userId;
    }

    public function isGuest(): bool
    {
        return $this->identity()->isGuest();
    }

    /**
     * Whether the visitor is a fully fledged member: signed in, registration approved and, when
     * the site asks for it, the address confirmed.
     *
     * Not the same question as isGuest(): the checks are made when signing in, but an account can
     * lose its approval — or the site can start asking for confirmed addresses — while a session
     * of it is still alive.
     */
    public function isValid(): bool
    {
        return ! $this->isGuest() && $this->user()->isValid();
    }

    /**
     * The profile of the visitor: nickname, settings, counters — the fields the identity has no
     * business carrying.
     *
     * Loaded on the first ask and not before, so a page that only checks permissions costs no
     * query. A guest is an unsaved model rather than null: the templates and the settings readers
     * ask the same questions of everybody, and the defaults of an empty user are the right
     * answers for a visitor who is not signed in.
     */
    public function user(): User
    {
        if ($this->user === null) {
            $id = $this->id();
            $this->user = ($id === 0 ? null : $this->users->find($id)) ?? new User();
        }

        return $this->user;
    }

    public function reset(): void
    {
        $this->identity = null;
        $this->user = null;
    }

    private function resolve(): Identity
    {
        $request = $this->requestStack->getCurrentRequest();

        // Console commands, cron and the scheduler run without a request: nobody is signed in,
        // and asking for the current user there must not be an error — it is a guest.
        $identity = $request instanceof Request
            ? $this->authenticators->authenticate($request)
            : Identity::guest();

        // Who they are and what they may do are two separate questions, answered in that order.
        return $this->permissions->resolve($identity);
    }
}
