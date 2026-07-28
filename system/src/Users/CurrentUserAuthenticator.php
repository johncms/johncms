<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Users;

use Johncms\Http\Request;
use Johncms\System\Users\User as LegacyUser;
use Johncms\System\Users\UserFactory as LegacyUserFactory;
use RuntimeException;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Loads the visitor of the request being served into the two shared current-user services.
 *
 * Both of them are singletons that dozens of controllers and services take in their constructor,
 * so they cannot be rebuilt per request — instead their state is replaced here, once per request:
 * the boot calls this before anything reads the user (the translator picks the locale from it),
 * and the kernel calls it again for every request of a long-running runtime.
 */
final class CurrentUserAuthenticator
{
    /**
     * The request the shared instances currently hold the user of. Under FPM the boot and the
     * kernel serve the same request object, so the second call is a no-op instead of a second
     * round of authentication queries.
     */
    private ?Request $authenticatedFor = null;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly LegacyUserFactory $legacyUserFactory,
        private readonly LegacyUser $legacyUser,
        private readonly UserFactory $userFactory,
        private readonly User $user,
    ) {
    }

    public function authenticate(): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if (! $request instanceof Request) {
            throw new RuntimeException('No request is being served: the request stack is empty.');
        }

        if ($this->authenticatedFor === $request) {
            return;
        }

        $this->authenticatedFor = $request;

        // The legacy user goes first, as it did when it was the boot that resolved it: it is the
        // one that records the IP history, and the Eloquent user then finds the address already
        // up to date and writes nothing.
        $this->legacyUserFactory->authenticate($this->legacyUser, $request);
        $this->userFactory->authenticate($this->user, $request);
    }
}
