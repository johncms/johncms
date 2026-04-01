<?php

declare(strict_types=1);

namespace Johncms\Router;

use Johncms\System\Http\Request;
use Symfony\Component\Routing\RequestContext;

final class RequestContextFactory
{
    public function __construct(
        private readonly Request $request
    ) {
    }

    public function __invoke(): RequestContext
    {
        $uri = $this->request->getUri();
        return new RequestContext(
            '',
            $this->request->getMethod(),
            $uri->getHost() !== '' ? $uri->getHost() : 'localhost',
            $this->request->isHttps() ? 'https' : 'http',
            80,
            443,
            $uri->getPath() !== '' ? $uri->getPath() : '/',
            $uri->getQuery()
        );
    }
}
