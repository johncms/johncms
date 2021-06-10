<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\System\Router\Strategy;

use GuzzleHttp\Psr7\Response;
use Johncms\System\Router\ParametersInjector;
use JsonSerializable;
use League\Route\Http\Exception\{MethodNotAllowedException, NotFoundException};
use League\Route\Route;
use League\Route\Strategy\AbstractStrategy;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\{ResponseFactoryInterface, ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};
use Throwable;

class ApplicationStrategy extends AbstractStrategy
{
    protected ContainerInterface $container;
    protected ResponseFactoryInterface $responseFactory;

    public function __construct(ResponseFactoryInterface $responseFactory, ContainerInterface $container)
    {
        $this->responseFactory = $responseFactory;
        $this->container = $container;
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
                try {
                    return $handler->handle($request);
                } catch (Throwable $e) {
                    throw $e;
                }
            }
        };
    }

    public function invokeRouteCallable(Route $route, ServerRequestInterface $request): ResponseInterface
    {
        $controller = $route->getCallable($this->getContainer());

        $params = [];
        if (is_array($controller) && is_object($controller[0]) && ! empty($controller[1])) {
            $injector = new ParametersInjector($this->container);
            $params = $injector->injectParameters($controller[0], $controller[1], $route->getVars());
        }

        $response = $controller(...array_values($params));
        $response = $this->prepareResponse($response);

        return $this->decorateResponse($response);
    }

    /**
     * @param mixed $response_content
     * @return ResponseInterface
     * @noinspection PhpIncompatibleReturnTypeInspection
     */
    protected function prepareResponse($response_content): ResponseInterface
    {
        if (is_string($response_content)) {
            $response = new Response();
            $response->getBody()->write($response_content);
            return $response;
        } elseif ($response_content === null) {
            return new Response(204);
        } elseif ($this->isJsonSerializable($response_content)) {
            $response = new Response();
            $response->getBody()->write(json_encode($response_content));
            return $response->withAddedHeader('content-type', 'application/json');
        }

        return $response_content;
    }

    /**
     * @param mixed $response
     * @return bool
     */
    protected function isJsonSerializable($response): bool
    {
        if ($response instanceof ResponseInterface) {
            return false;
        }

        return (is_array($response) || is_object($response) || $response instanceof JsonSerializable);
    }

    protected function throwThrowableMiddleware(Throwable $error): MiddlewareInterface
    {
        return new class ($error) implements MiddlewareInterface {
            protected Throwable $error;

            public function __construct(Throwable $error)
            {
                $this->error = $error;
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                throw $this->error;
            }
        };
    }

    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    public function setContainer(ContainerInterface $container): ApplicationStrategy
    {
        $this->container = $container;
        return $this;
    }
}
