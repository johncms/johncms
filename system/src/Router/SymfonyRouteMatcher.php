<?php

declare(strict_types=1);

namespace Johncms\Router;

use Johncms\Http\Request;
use Johncms\Http\RequestPathNormalizer;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\RequestContext;

final class SymfonyRouteMatcher
{
    public function __construct(
        private readonly UrlMatcherInterface $matcher,
        private readonly RequestContext $context,
        private readonly RequestPathNormalizer $pathNormalizer,
    ) {
    }

    /**
     * Matches a request against the route collection.
     *
     * The context is refreshed from the request on every call, so a long-running worker cannot
     * generate URLs from the host and scheme of an earlier request. Matching itself uses the
     * normalized path rather than UrlMatcher::matchRequest(), because the routes are declared
     * decoded and without a trailing slash (see RequestPathNormalizer).
     */
    public function matchRequest(Request $request): RouteMatchResult
    {
        $this->context->fromRequest($request);

        return $this->dispatch($request->getMethod(), $this->pathNormalizer->normalize($request));
    }

    public function dispatch(string $method, string $path): RouteMatchResult
    {
        $this->context->setMethod($method);

        try {
            $attributes = $this->matcher->match($path);
            $handler = $attributes['_handler'] ?? null;
            $middlewares = isset($attributes['_middlewares']) && is_array($attributes['_middlewares'])
                ? $attributes['_middlewares']
                : [];
            unset($attributes['_handler'], $attributes['_middlewares'], $attributes['_route'], $attributes['_route_mapping']);

            return new RouteMatchResult(
                status: RouteMatchResult::FOUND,
                handler: $handler,
                params: $attributes,
                middlewares: $middlewares,
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
