<?php

declare(strict_types=1);

namespace Johncms\Router;

use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\RequestContext;

final class SymfonyRouteMatcher
{
    public function __construct(
        private readonly UrlMatcherInterface $matcher,
        private readonly RequestContext $context,
    ) {
    }

    public function dispatch(string $method, string $path): RouteMatchResult
    {
        $this->context->setMethod($method);

        try {
            $attributes = $this->matcher->match($path);
            $handler = $attributes['_handler'] ?? null;
            unset($attributes['_handler'], $attributes['_route'], $attributes['_route_mapping']);

            return new RouteMatchResult(
                status: RouteMatchResult::FOUND,
                handler: $handler,
                params: $attributes,
            );
        } catch (MethodNotAllowedException $exception) {
            return new RouteMatchResult(
                status: RouteMatchResult::METHOD_NOT_ALLOWED,
                allowedMethods: $exception->getAllowedMethods(),
            );
        } catch (ResourceNotFoundException) {
            return new RouteMatchResult(status: RouteMatchResult::NOT_FOUND);
        }
    }
}
