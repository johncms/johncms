<?php

declare(strict_types=1);

namespace Johncms\Router;

use Illuminate\Container\Container;
use Johncms\Http\Request;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection as SymfonyRouteCollection;

final class Router
{
    private SymfonyRouteCollection $routeCollection;

    public function __construct(
        private readonly RouteLoader $loader,
        private readonly RequestContext $context,
        private readonly Request $request,
        private readonly Container $container,
        private readonly ParametersInjector $parametersInjector,
    ) {
    }

    public function getRouteCollection(): SymfonyRouteCollection
    {
        return $this->routeCollection ??= $this->loader->load()->compile();
    }

    public function getUrlMatcher(): UrlMatcherInterface
    {
        return new UrlMatcher($this->getRouteCollection(), $this->context);
    }

    public function handleRequest(): ResponseInterface
    {
        $requestHandler = new RequestHandler($this->getUrlMatcher(), $this->container, $this->parametersInjector);
        return $requestHandler->handle($this->request);
    }
}
