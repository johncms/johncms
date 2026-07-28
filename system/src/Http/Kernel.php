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
use Johncms\Mail\EmailSender;
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
use Symfony\Component\HttpKernel\TerminableInterface;
use Throwable;

/**
 * Turns a request into a response: route matching, the middleware pipeline, the controller and
 * the mapping of the HTTP control-flow exceptions.
 *
 * Implements HttpKernelInterface without pulling in symfony/http-kernel's event stack — the
 * interface is what the symfony/runtime bridges to FrankenPHP and RoadRunner expect,
 * while routing and the pipeline stay the project's own MiddlewareDispatcher + ActionInvoker.
 *
 * TerminableInterface carries the post-response work (UserStat, the mail queue) — see terminate().
 */
final readonly class Kernel implements HttpKernelInterface, TerminableInterface
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
        private Session $session,
    ) {
    }

    public function handle(
        HttpFoundationRequest $request,
        int $type = self::MAIN_REQUEST,
        bool $catch = true
    ): Response {
        if ($type !== self::MAIN_REQUEST) {
            // Nothing issues sub-requests today, and handle() has main-request-only side effects:
            // it republishes the request into the container, with nothing restoring the parent
            // afterwards (Symfony solves this with RequestStack).
            throw new LogicException('The kernel does not support sub-requests.');
        }

        if (! $request instanceof Request) {
            // Runtime bridges that build a plain HttpFoundation request (RoadRunner, Swoole) need
            // an adapter rebuilding our subclass from the bags, which does not exist yet.
            throw new LogicException(
                sprintf('The kernel expects a %s, %s given.', Request::class, get_debug_type($request))
            );
        }

        $this->container->set(Request::class, $request);

        // Under FPM the boot already started the session for this request, so this is a no-op —
        // it is here to keep the per-request start in one place for the worker runtime, where the
        // boot runs once and every cycle after the first arrives with the session closed by save()
        // below. Native storage cannot be restarted once headers are sent, so a worker will also
        // need a different storage in SessionFactory.
        if ($this->sessionIsEnabled()) {
            $this->session->start();
        }

        $response = $catch ? $this->handleCaught($request) : $this->handleRaw($request);

        // The session must be closed before the response reaches the client, not after: send()
        // detaches the client connection (fastcgi_finish_request()) before terminate() runs, and
        // PHP otherwise keeps the session file locked until script shutdown — the next request
        // from the same visitor would then queue behind terminate()'s work (the mail batch).
        if ($this->sessionIsEnabled() && $this->session->isStarted()) {
            $this->session->save();
        }

        return $response;
    }

    /**
     * Console runs (cron, commands, the functional test harness) get in-memory session storage
     * from SessionFactory: there is nothing to open or write there, so the kernel leaves the
     * session alone.
     */
    private function sessionIsEnabled(): bool
    {
        return ! defined('CONSOLE_MODE') || CONSOLE_MODE === false;
    }

    private function handleCaught(Request $request): Response
    {
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

    public function terminate(HttpFoundationRequest $request, Response $response): void
    {
        // Register the location of the visitor on the site. Moved here from handleRaw(): it is a
        // post-response side effect (a database write), not something the response depends on.
        new UserStat($this->container);

        // Only successfully served requests flush the mail queue: a redirect or an error page has
        // no business running it. Kept 1:1 with the condition that used to live in public/index.php.
        if (! USE_CRON && ! defined('_IN_JOHNADM') && $response->isSuccessful()) {
            $cronCache = CACHE_PATH . 'cron.cache';
            if (! file_exists($cronCache) || filemtime($cronCache) < (time() - 5)) {
                EmailSender::send();
                file_put_contents($cronCache, time());
            }
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

        $request->attributes->add($match->params);

        // The handler passed to the pipeline already returns a Response: normalizing here, before
        // the middleware stack runs, is what lets MiddlewareInterface::handle() be typed to
        // Response instead of mixed. The transitional contract (a controller action may still
        // return a Response, a string or null) is unchanged — it is just enforced one call earlier.
        return $this->middlewareDispatcher->dispatch(
            request: $request,
            middlewares: [TrimStringsMiddleware::class, ...$match->middlewares],
            handler: fn (Request $request): Response => $this->responseNormalizer->normalize(
                $this->invokeController($match->handler, $request, $match->params)
            ),
        );
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
