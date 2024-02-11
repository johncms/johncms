<?php

declare(strict_types=1);

namespace Johncms\Router;

use Illuminate\Contracts\Container\Container;
use Johncms\Http\Request;
use Symfony\Component\Routing\RequestContext;

class RouterFactory
{
    public function __invoke(Container $container): Router
    {
        $request = $container->get(Request::class);
        $context = new RequestContext(
            '',
            $request->getMethod(),
            $request->getUri()->getHost(),
            $request->isHttps() ? 'https' : 'http',
            80,
            443,
            $request->getUri()->getPath(),
            $request->getUri()->getQuery()
        );

        return new Router(
            $container->get(RouteLoader::class),
            $context,
            $request,
            $container,
            $container->get(ParametersInjector::class),
            $container->get(MiddlewareDispatcher::class)
        );
    }
}
