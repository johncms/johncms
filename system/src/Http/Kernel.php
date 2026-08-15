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
use Johncms\Http\Middleware\CsrfMiddleware;
use Johncms\Http\Middleware\RequirePermissionMiddleware;
use Johncms\Http\Middleware\TrimStringsMiddleware;
use Johncms\Logs\DebugDetailsPolicy;
use Johncms\Router\MiddlewareDispatcher;
use Johncms\Router\Route;
use Johncms\Router\RouteMatchResult;
use Johncms\Router\SymfonyRouteMatcher;
use Johncms\Security\RequestRateLogInterface;
use Johncms\System\i18n\LocaleResolver;
use Johncms\System\i18n\Translator;
use Johncms\System\Users\UserStat;
use Johncms\Users\IpHistoryRecorder;
use LogicException;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\Exception\RequestExceptionInterface;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\Request as HttpFoundationRequest;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;
use Symfony\Contracts\Service\ResetInterface;
use Throwable;

/**
 * Turns a request into a response: route matching, the middleware pipeline, the controller and
 * the mapping of the HTTP control-flow exceptions.
 *
 * Implements HttpKernelInterface without pulling in symfony/http-kernel's event stack — the
 * interface is what the symfony/runtime bridges to FrankenPHP and RoadRunner expect,
 * while routing and the pipeline stay the project's own MiddlewareDispatcher + ActionInvoker.
 *
 * TerminableInterface carries the post-response work (UserStat) — see terminate().
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
        private RequestStack $requestStack,
        private RequestRateLogInterface $requestRateLog,
        private CookieQueue $cookieQueue,
        private IpHistoryRecorder $ipHistoryRecorder,
        private LocaleResolver $localeResolver,
        private Translator $translator,
        private ModuleContext $moduleContext,
        /** @var iterable<ResetInterface> Shared services caching something that belongs to one request. */
        private iterable $resettableServices,
    ) {
    }

    public function handle(
        HttpFoundationRequest $request,
        int $type = self::MAIN_REQUEST,
        bool $catch = true
    ): Response {
        if ($type !== self::MAIN_REQUEST) {
            // Nothing issues sub-requests today, and handle() has main-request-only side effects:
            // it starts the session, authenticates the visitor and resets the per-request state of
            // the shared services, none of which a sub-request may redo for the request around it.
            throw new LogicException('The kernel does not support sub-requests.');
        }

        if (! $request instanceof Request) {
            // Runtime bridges that build a plain HttpFoundation request (RoadRunner, Swoole) need
            // an adapter rebuilding our subclass from the bags, which does not exist yet.
            throw new LogicException(
                sprintf('The kernel expects a %s, %s given.', Request::class, get_debug_type($request))
            );
        }

        $this->requestStack->push($request);

        // Everything a shared service cached for the previous request is dropped here, before the
        // first of them is asked anything. Under FPM there is nothing to drop; in a worker this is
        // what keeps one visitor's address, breadcrumbs or page title out of the next answer.
        foreach ($this->resettableServices as $service) {
            $service->reset();
        }

        if ($this->isWebRuntime()) {
            // A write, so it belongs to the request cycle rather than to a service constructor:
            // resolving a service must not be what records a visit.
            $this->requestRateLog->record($this->clientIp($request) ?? '');

            // Under FPM the boot already started the session for this request, so this is a no-op —
            // it is here to keep the per-request start in one place for the worker runtime, where
            // the boot runs once and every cycle after the first arrives with the session closed by
            // save() below. Native storage cannot be restarted once headers are sent, so a worker
            // will also need a different storage in SessionFactory.
            $this->session->start();

            // Another write, and it runs before the controller: the address moved down into the
            // history is the one the visitor arrived with, and UserStat overwrites the visit
            // fields it reads once the response is out. Asking who the visitor is here is also
            // what runs the authenticator chain for this request.
            $this->ipHistoryRecorder->record();
        }

        // The language of this visitor, applied on top of the translator built at boot. Resolving
        // it needs the user, hence the order; setting the same locale again is a no-op, so under
        // FPM this costs nothing.
        $this->translator->setLocale($this->localeResolver->resolve());

        try {
            $response = $catch ? $this->handleCaught($request) : $this->handleRaw($request);

            // Cookies decided on earlier in the cycle — the reissued sign-in cookie, for one —
            // are attached here, where a response finally exists to carry them.
            $this->cookieQueue->applyTo($response);

            return $response;
        } finally {
            // Pop even when handleRaw() throws (catch = false), or the stack grows by one request
            // per failed cycle and getCurrentRequest() keeps answering with a request already served.
            $this->requestStack->pop();

            // The session must be closed before the response reaches the client, not after: send()
            // detaches the client connection (fastcgi_finish_request()) before terminate() runs, and
            // PHP otherwise keeps the session file locked until script shutdown — the next request
            // from the same visitor would then queue behind terminate()'s work.
            //
            // In the finally block rather than after it, so a cycle that throws still closes the
            // session it opened. That is what lets reset() treat an open session as belonging to
            // the request being served: a worker cycle can never inherit one left open by the
            // previous one, and answer a visitor with the session of whoever crashed before them.
            if ($this->isWebRuntime() && $this->session->isStarted()) {
                $this->session->save();
            }
        }
    }

    /**
     * Whether this process is serving an actual HTTP request. Console runs — cron, commands, the
     * functional test harness — must not open a session (SessionFactory gives them in-memory
     * storage instead) and must not record a visit in the request-rate log.
     */
    private function isWebRuntime(): bool
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

        // The permission of the route travels to the middleware on the request: the middleware is
        // a singleton and cannot be told which permission to ask any other way.
        if ($match->permission !== null) {
            $request->attributes->set(Route::PERMISSION_ATTRIBUTE, $match->permission);
            $request->attributes->set(Route::PERMISSION_HIDDEN_ATTRIBUTE, $match->permissionHidden);
        }

        // The translations of the module owning the route, before anything renders. Belongs to the
        // cycle rather than to the controller: controllers are built per request only because of
        // work like this, and a guard rejecting the request must answer in the right language too.
        $this->moduleContext->enter($match->module);

        // The handler passed to the pipeline already returns a Response: normalizing here, before
        // the middleware stack runs, is what lets MiddlewareInterface::handle() be typed to
        // Response instead of mixed. The transitional contract (a controller action may still
        // return a Response, a string or null) is unchanged — it is just enforced one call earlier.
        return $this->middlewareDispatcher->dispatch(
            request: $request,
            middlewares: [
                TrimStringsMiddleware::class,
                // Before the middlewares of the route, so a forged request is rejected without
                // reaching the logic of the module. Routes opt out with Route::withoutCsrf().
                ...($match->csrfExempt ? [] : [CsrfMiddleware::class]),
                // Before the middlewares of the route: whoever may not open the route at all has
                // no business reaching the guards of the module behind it.
                ...($match->permission !== null ? [RequirePermissionMiddleware::class] : []),
                ...$match->middlewares,
            ],
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
