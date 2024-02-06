<?php

declare(strict_types=1);

namespace Johncms\Router;

use GuzzleHttp\Psr7\Response;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Johncms\Exceptions\PageNotFoundException;
use Johncms\Http\Response\RedirectResponse;
use JsonException;
use JsonSerializable;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Throwable;

class RequestHandler implements RequestHandlerInterface, MiddlewareInterface
{
    public function __construct(
        private readonly UrlMatcherInterface $urlMatcher,
        private readonly Container $container,
        private readonly ParametersInjector $parametersInjector,
        private readonly MiddlewareDispatcher $middlewareDispatcher,
    ) {
    }

    /**
     * @throws Throwable
     * @throws JsonException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $routeData = $this->urlMatcher->match($request->getUri()->getPath());
            // Set route data to the container
            $this->container->instance('route', $routeData);

            foreach ($routeData['_middlewares'] as $middleware) {
                $this->middlewareDispatcher->addMiddleware($middleware);
            }

            $this->middlewareDispatcher->addMiddleware($this);
            return $this->middlewareDispatcher->handle($request);
        } catch (ResourceNotFoundException) {
            return status_page(404);
        } catch (ModelNotFoundException $exception) {
            return status_page(404, message: $exception->getMessage());
        } catch (PageNotFoundException $exception) {
            return status_page(404, template: $exception->getTemplate(), title: $exception->getTitle(), message: $exception->getMessage());
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws JsonException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $routeData = $this->container->get('route');
        if ($redirect = $this->handleRedirect($request, $routeData)) {
            return $redirect;
        }

        return $this->invokeRouteCallable($routeData);
    }

    public function handleRedirect(ServerRequestInterface $request, array $routeData): ?RedirectResponse
    {
        if (! array_key_exists('_redirect', $routeData)) {
            return null;
        }
        $redirectUri = $routeData['_redirect']['path'];
        $query = $request->getUri()->getQuery();
        if ($query != '') {
            $redirectUri .= '?' . $query;
        }
        return new RedirectResponse($redirectUri);
    }

    /**
     * @throws JsonException
     */
    public function invokeRouteCallable(array $routeData): ResponseInterface
    {
        $controller = $routeData['_controller'];
        $callable = $this->getCallable($controller);
        $params = [];
        if (is_array($callable) && is_object($callable[0]) && ! empty($callable[1])) {
            $params = $this->parametersInjector->injectParameters($callable[0], $callable[1], $routeData);
        }

        $response = $callable(...array_values($params));
        return $this->prepareResponse($response);
    }

    /**
     * @throws JsonException
     */
    protected function prepareResponse(mixed $responseContent): ResponseInterface
    {
        if (is_string($responseContent)) {
            $response = new Response();
            $response->getBody()->write($responseContent);
            return $response;
        } elseif ($responseContent === null) {
            return new Response(204);
        } elseif ($this->isJsonSerializable($responseContent)) {
            $response = new Response(200, ['content-type' => 'application/json']);
            $response->getBody()->write(json_encode($responseContent, JSON_THROW_ON_ERROR));
            return $response;
        }

        return $responseContent;
    }

    protected function isJsonSerializable(mixed $response): bool
    {
        if ($response instanceof ResponseInterface) {
            return false;
        }

        return (is_array($response) || is_object($response) || $response instanceof JsonSerializable);
    }

    public function getCallable(mixed $callable): callable
    {
        if (is_array($callable) && isset($callable[0]) && is_object($callable[0])) {
            $callable = [$callable[0], $callable[1]];
        }

        if (is_array($callable) && isset($callable[0]) && is_string($callable[0])) {
            $callable = [$this->container->get($callable[0]), $callable[1]];
        }

        if (is_string($callable)) {
            $callable = $this->container->get($callable);
        }

        if (! is_callable($callable)) {
            throw new \RuntimeException('Could not resolve a callable for this route');
        }

        return $callable;
    }
}
