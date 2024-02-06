<?php

declare(strict_types=1);

namespace Johncms\Router;

use Illuminate\Contracts\Container\Container;
use Johncms\Http\Request;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection as SymfonyRouteCollection;

final class Router
{
    private SymfonyRouteCollection $routeCollection;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(
        private readonly RouteLoader $loader,
        private readonly RequestContext $context,
        private readonly Request $request,
        private readonly Container $container,
        private readonly ParametersInjector $parametersInjector,
        private readonly MiddlewareDispatcher $middlewareDispatcher,
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

    /**
     * @throws \Throwable
     * @throws \JsonException
     */
    public function handleRequest(): ResponseInterface
    {
        $requestHandler = new RequestHandler(
            $this->getUrlMatcher(),
            $this->container,
            $this->parametersInjector,
            $this->middlewareDispatcher
        );
        return $requestHandler->handle($this->request);
    }

    public function getUrlGenerator(): UrlGenerator
    {
        return $this->urlGenerator ??= new UrlGenerator($this->getRouteCollection(), $this->context);
    }
}
