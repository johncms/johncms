<?php

declare(strict_types=1);

namespace Johncms\Http\Controller;

use Johncms\Auth\Impersonation\ImpersonationManager;
use Johncms\Auth\Impersonation\ImpersonationNotAllowedException;
use Johncms\Http\ExceptionResponseFactory;
use Johncms\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Entering and leaving "browse as this user".
 *
 * In the core rather than in the admin module, for the reason the sign-in use case is: leaving
 * has to work on every page of the site, and a site can be running with the panel or the login
 * module switched off. Only the button that starts it belongs to the panel.
 */
final readonly class ImpersonationController
{
    public function __construct(
        private ImpersonationManager $impersonation,
        private ExceptionResponseFactory $exceptionResponses,
    ) {
    }

    public function start(Request $request, int $id): Response
    {
        try {
            $this->impersonation->start($id, $request);
        } catch (ImpersonationNotAllowedException $exception) {
            // The reason is shown rather than swallowed: "outranks you" and "no such account" are
            // different problems, and an administrator can act on either.
            return $this->exceptionResponses->forbidden(message: $exception->getMessage());
        }

        redirect('/');
    }

    public function stop(Request $request): void
    {
        $this->impersonation->stop($request);

        redirect('/');
    }
}
