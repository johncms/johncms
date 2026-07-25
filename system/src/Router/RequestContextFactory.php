<?php

declare(strict_types=1);

namespace Johncms\Router;

use Symfony\Component\Routing\RequestContext;

/**
 * Builds an empty routing context.
 *
 * It deliberately does not read the request: resolving the host here happened while the container
 * was still building the kernel, so a Host header failing the trusted-host patterns raised
 * SuspiciousOperationException outside Kernel::handle() and answered 500 with an error log entry
 * per request. SymfonyRouteMatcher::matchRequest() fills the context from the request it matches,
 * which is also what keeps it correct for a long-running worker.
 */
final class RequestContextFactory
{
    public function __invoke(): RequestContext
    {
        return new RequestContext();
    }
}
