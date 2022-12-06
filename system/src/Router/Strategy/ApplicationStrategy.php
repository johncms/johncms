<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Router\Strategy;

use GuzzleHttp\Psr7\Response;
use Illuminate\Container\Container;
use Johncms\Container\ContainerFactory;
use Johncms\Router\ParametersInjector;
use JsonSerializable;
use League\Route\Http\Exception\{MethodNotAllowedException, NotFoundException};
use League\Route\Route;
use League\Route\Strategy\AbstractStrategy;
use Psr\Http\Message\{ResponseFactoryInterface, ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};
use Throwable;

class ApplicationStrategy extends AbstractStrategy
{
    public function __construct(protected ResponseFactoryInterface $responseFactory)
    {
    }

    public function getMethodNotAllowedDecorator(MethodNotAllowedException $exception): MiddlewareInterface
    {
        return $this->throwThrowableMiddleware($exception);
    }

    public function getNotFoundDecorator(NotFoundException $exception): MiddlewareInterface
    {
        return $this->throwThrowableMiddleware($exception);
    }

    public function getThrowableHandler(): MiddlewareInterface
    {
        return new class implements MiddlewareInterface {
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                return $handler->handle($request);
            }
        };
    }

    public function invokeRouteCallable(Route $route, ServerRequestInterface $request): ResponseInterface
    {
        $container = $this->getContainer();
        // Set current route to container
        $container->instance('route', $route);
        $controller = $route->getCallable($container);

        $params = [];
        if (is_array($controller) && is_object($controller[0]) && ! empty($controller[1])) {
            $injector = new ParametersInjector($this->getContainer());
            $params = $injector->injectParameters($controller[0], $controller[1], $route->getVars());
        }

        $response = $controller(...array_values($params));
        $response = $this->prepareResponse($response);

        return $this->decorateResponse($response);
    }

    protected function prepareResponse(mixed $responseContent): ResponseInterface
    {
        if (is_string($responseContent)) {
            $response = new Response();
            $response->getBody()->write($responseContent);
            return $response;
        } elseif ($responseContent === null) {
            return new Response(204);
        } elseif ($this->isJsonSerializable($responseContent)) {
            $response = new Response();
            $response->getBody()->write(json_encode($responseContent, JSON_THROW_ON_ERROR));
            return $response->withAddedHeader('content-type', 'application/json');
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

    protected function throwThrowableMiddleware(Throwable $error): MiddlewareInterface
    {
        return new class ($error) implements MiddlewareInterface {
            public function __construct(protected Throwable $error)
            {
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                throw $this->error;
            }
        };
    }

    public function getContainer(): Container
    {
        return ContainerFactory::getContainer();
    }
}
