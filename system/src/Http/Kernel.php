<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Http;

use Johncms\Exceptions\HttpRedirectException;
use Johncms\Exceptions\MethodNotAllowedException;
use Johncms\Exceptions\PageNotFoundException;
use Johncms\Http\Controller\ActionInvoker;
use Johncms\Http\Middleware\TrimStringsMiddleware;
use Johncms\Logs\DebugDetailsPolicy;
use Johncms\Router\MiddlewareDispatcher;
use Johncms\Router\RouteMatchResult;
use Johncms\Router\SymfonyRouteMatcher;
use Johncms\System\Users\UserStat;
use LogicException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Exception\RequestExceptionInterface;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\Request as HttpFoundationRequest;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Throwable;

/**
 * Turns a request into a response: route matching, the middleware pipeline, the controller and
 * the mapping of the HTTP control-flow exceptions (plan stage 3a).
 *
 * Implements HttpKernelInterface without pulling in symfony/http-kernel's event stack — the
 * interface is what the symfony/runtime bridges to FrankenPHP and RoadRunner expect (stage 6),
 * while routing and the pipeline stay the project's own MiddlewareDispatcher + ActionInvoker.
 *
 * TerminableInterface and the post-response work (UserStat, the mail queue) follow in stage 3b.
 */
final readonly class Kernel implements HttpKernelInterface
{
    public function __construct(
        private ContainerInterface $container,
        private SymfonyRouteMatcher $routeMatcher,
        private MiddlewareDispatcher $middlewareDispatcher,
        private ActionInvoker $actionInvoker,
        private ResponseNormalizer $responseNormalizer,
        private ExceptionResponseFactory $exceptionResponses,
        private DebugDetailsPolicy $debugDetailsPolicy,
        private LoggerInterface $logger,
    ) {
    }

    public function handle(
        HttpFoundationRequest $request,
        int $type = self::MAIN_REQUEST,
        bool $catch = true
    ): Response {
        if ($type !== self::MAIN_REQUEST) {
            // Nothing issues sub-requests today, and handle() has main-request-only side effects:
            // it republishes the request into the container and resets the legacy status code,
            // with nothing restoring the parent afterwards (Symfony solves this with RequestStack).
            throw new LogicException('The kernel does not support sub-requests.');
        }

        if (! $request instanceof Request) {
            // Runtime bridges that build a plain HttpFoundation request (RoadRunner, Swoole) need
            // an adapter rebuilding our subclass from the bags — that comes with stage 6.
            throw new LogicException(
                sprintf('The kernel expects a %s, %s given.', Request::class, get_debug_type($request))
            );
        }

        // http_response_code() is process-global and nothing resets it between two handle() calls
        // in one process. Legacy controllers still set the status through it (stage 2c), so a 403
        // from an earlier request would otherwise become the status of this one.
        http_response_code(Response::HTTP_OK);

        $this->container->set(Request::class, $request);

        if (! $catch) {
            return $this->handleRaw($request);
        }

        try {
            return $this->handleRaw($request);
        } catch (HttpRedirectException $exception) {
            return $this->exceptionResponses->fromRedirect($exception);
        } catch (PageNotFoundException $exception) {
            return $this->exceptionResponses->fromPageNotFound($exception);
        } catch (MethodNotAllowedException $exception) {
            return $this->exceptionResponses->fromMethodNotAllowed($exception);
        } catch (SessionNotFoundException $throwable) {
            // Also a RequestExceptionInterface, but it means the application asked for a session
            // that was never started — a server-side fault, not a malformed request.
            return $this->serverError($throwable, $request);
        } catch (RequestExceptionInterface $exception) {
            // Malformed request: an array in a scalar parameter, an undecodable JSON body, a Host
            // that failed the trusted-host patterns, conflicting forwarded headers. All of them
            // are the client's fault, so they answer 400 and are logged at info level instead of
            // error — an unauthenticated client must not be able to fill the error log in a loop.
            $this->logger->info($exception->getMessage(), $this->errorContext($exception, $request));

            return $this->exceptionResponses->badRequest();
        } catch (Throwable $throwable) {
            return $this->serverError($throwable, $request);
        }
    }

    private function handleRaw(Request $request): Response
    {
        $match = $this->routeMatcher->matchRequest($request);

        if ($match->status === RouteMatchResult::METHOD_NOT_ALLOWED) {
            throw new MethodNotAllowedException($match->allowedMethods);
        }

        if ($match->status !== RouteMatchResult::FOUND) {
            pageNotFound();
        }

        // Register the location of the visitor on the site
        new UserStat($this->container);

        $request->attributes->add($match->params);

        $result = $this->middlewareDispatcher->dispatch(
            request: $request,
            middlewares: [TrimStringsMiddleware::class, ...$match->middlewares],
            handler: fn (Request $request): mixed => $this->invokeController($match->handler, $request, $match->params),
        );

        // Transitional contract: an action returns a Response, a string or nothing. The status is
        // read from http_response_code() because controllers that still set it that way would
        // otherwise have their status overwritten by the one of the response (stage 2c).
        return $this->responseNormalizer->normalize($result, http_response_code() ?: Response::HTTP_OK);
    }

    /**
     * @param array<string, mixed> $routeParams
     */
    private function invokeController(mixed $handler, Request $request, array $routeParams): mixed
    {
        if (is_array($handler) && isset($handler[0], $handler[1]) && class_exists($handler[0])) {
            return $this->actionInvoker->invoke(
                controller:  $this->container->get($handler[0]),
                method:      $handler[1],
                request:     $request,
                routeParams: $routeParams,
            );
        }

        if (is_string($handler) && class_exists($handler) && method_exists($handler, '__invoke')) {
            return $this->actionInvoker->invoke(
                controller:  $this->container->get($handler),
                method:      '__invoke',
                request:     $request,
                routeParams: $routeParams,
            );
        }

        throw new RuntimeException(
            sprintf('Route handler could not be resolved to a controller: %s', var_export($handler, true))
        );
    }

    private function serverError(Throwable $throwable, Request $request): Response
    {
        $this->logger->error($throwable->getMessage(), $this->errorContext($throwable, $request));

        return $this->exceptionResponses->internalServerError($throwable, $this->debugDetailsPolicy->allowed());
    }

    /**
     * The address resolution itself can raise (conflicting forwarded headers), and that must not
     * replace the failure being logged.
     */
    private function clientIp(Request $request): ?string
    {
        try {
            return $request->getClientIp();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Mirrors the context GlobalErrorHandler records, but takes the request facts from the request
     * the kernel is handling instead of the superglobals.
     *
     * @return array<string, mixed>
     */
    private function errorContext(Throwable $throwable, Request $request): array
    {
        $context = [
            'file'      => $throwable->getFile(),
            'line'      => $throwable->getLine(),
            'url'       => $request->getRequestUri(),
            'method'    => $request->getMethod(),
            'ip'        => $this->clientIp($request),
            'exception' => $throwable,
        ];

        if (method_exists($throwable, 'context')) {
            $context = array_merge($context, $throwable->context());
        }

        return $context;
    }
}
