<?php

declare(strict_types=1);

namespace Tests\Functional\Http;

use Johncms\Http\Environment;
use Johncms\Http\Request;
use Johncms\NavChain;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;

/**
 * Acceptance test of the request scope: two sequential handle() calls in one process must not leak
 * into each other.
 *
 * Under FPM none of this can be observed — the process ends with the request. It is the worker
 * runtime this guards, and every assertion here corresponds to a leak that was real once:
 * controllers held the first request of the process in their constructor, breadcrumbs kept
 * growing, Environment cached the address of whoever came first.
 *
 * The comparisons take the whole response, head included: the title, canonical, keywords and
 * description of a page are data handed to a template for that render, so they must not differ
 * between two identical requests either.
 */
final class RequestIsolationTest extends FunctionalTestCase
{
    /**
     * The same request twice has to produce the same answer, byte for byte. Anything a shared
     * service keeps from the first cycle shows up as a difference — accumulated breadcrumbs being
     * the case that actually happened.
     */
    public function testTheSameRequestTwiceProducesTheSameResponse(): void
    {
        $first = $this->handleRequest('/help');
        $second = $this->handleRequest('/help');

        self::assertSame(Response::HTTP_OK, $first->getStatusCode());
        self::assertSame($first->getStatusCode(), $second->getStatusCode());
        self::assertSame(
            $this->body($first),
            $this->body($second),
            'The second identical request answered differently: a shared service kept state of the first one.'
        );
    }

    /**
     * A request between two identical ones must not change what they answer either: the leak of a
     * singleton controller is visible only once another route has been served through it.
     */
    public function testARequestInBetweenDoesNotChangeTheAnswerOfTheNextOne(): void
    {
        $first = $this->handleRequest('/help');
        $this->handleRequest('/forum');
        $third = $this->handleRequest('/help');

        self::assertSame($this->body($first), $this->body($third));
    }

    /**
     * The point of stage 5b: controllers are container singletons, so a Request taken in the
     * constructor was the first request of the process for every cycle after it. Two searches
     * through the same controller instance is where that used to be visible.
     */
    public function testAControllerAnswersTheRequestItIsServing(): void
    {
        $first = (string) $this->handleRequest('/community/search?search=alphaquery')->getContent();
        $second = (string) $this->handleRequest('/community/search?search=betaquery')->getContent();

        self::assertStringContainsString('value="alphaquery"', $first);
        self::assertStringContainsString(
            'value="betaquery"',
            $second,
            'The controller answered with the query of the previous request.'
        );
        self::assertStringNotContainsString('alphaquery', $second);
    }

    /**
     * The same controller, this time through the collaborators that read the request without taking
     * it as an argument: PaginationFactory and PaginationGuard resolve it from the RequestStack.
     */
    public function testPaginationFollowsThePageOfTheCurrentRequest(): void
    {
        // Out of range for an empty result set, so the guard redirects to the last existing page.
        // Which page it redirects to is what proves it read this request rather than the previous one.
        $response = $this->handleRequest('/community/search?search=alphaquery&page=7');

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertStringNotContainsString('page=7', (string) $response->headers->get('Location', ''));

        $second = $this->handleRequest('/community/search?search=alphaquery');

        self::assertSame(
            Response::HTTP_OK,
            $second->getStatusCode(),
            'A request without a page was redirected: the guard still saw the page of the previous one.'
        );
    }

    public function testBreadcrumbsDoNotAccumulateAcrossRequests(): void
    {
        $navChain = $this->container()->get(NavChain::class);

        $this->handleRequest('/community/search');
        $afterFirst = $navChain->getAll();

        $this->handleRequest('/community/search');
        $afterSecond = $navChain->getAll();

        self::assertNotSame([], $afterFirst, 'The route builds no breadcrumbs, so the test proves nothing.');
        self::assertSame($afterFirst, $afterSecond, 'Breadcrumbs of the previous request survived into the next.');
    }

    /**
     * Environment caches the facts of the visitor it is asked about. The cache used to live as long
     * as the process, so the first visitor of a worker was reported as every visitor after them.
     *
     * Asserted from the outside of the cycle, which is the only place a test can look: the address
     * of a served request must be gone by then. What Environment answers instead is the address of
     * the boot request, the bottom entry of the stack — a cache surviving the cycle would answer
     * with the visitor of that cycle.
     */
    public function testTheVisitorFactsDoNotSurviveTheRequest(): void
    {
        $environment = $this->container()->get(Environment::class);

        $this->handleRequest('/help', server: ['REMOTE_ADDR' => '203.0.113.7']);

        self::assertNotSame(
            '203.0.113.7',
            $environment->getClientInfo()->ip,
            'Environment kept the address of the request that has been served.'
        );

        $this->handleRequest('/help', server: ['REMOTE_ADDR' => '203.0.113.9']);

        self::assertNotSame('203.0.113.9', $environment->getClientInfo()->ip);
    }

    /**
     * The stack must be back to its boot entry after every cycle: a cycle that leaves its request
     * behind makes getCurrentRequest() answer with a request already served, and the stack grow by
     * one entry per cycle. That the stack answers with the request being served *inside* a cycle is
     * what testPaginationFollowsThePageOfTheCurrentRequest above proves.
     */
    public function testTheStackIsBackToItsBootEntryAfterEveryCycle(): void
    {
        $requestStack = $this->container()->get(RequestStack::class);
        $bootRequest = $requestStack->getCurrentRequest();

        $this->handleRequest('/help?marker=first');

        self::assertSame(
            $bootRequest,
            $requestStack->getCurrentRequest(),
            'The request of a finished cycle is still on the stack.'
        );

        $this->handleRequest('/help?marker=second');

        self::assertSame($bootRequest, $requestStack->getCurrentRequest());
    }

    /**
     * The request is not a service: the only ways to it are the action argument and the stack. A
     * container entry would be a third one, and in a worker runtime a singleton frozen on the first
     * request of the process.
     */
    public function testTheRequestIsNotAServiceInTheContainer(): void
    {
        $this->expectException(NotFoundExceptionInterface::class);

        $this->container()->get(Request::class);
    }

    /**
     * The rendered page as it goes to the visitor, head included.
     */
    private function body(Response $response): string
    {
        $content = (string) $response->getContent();

        self::assertNotFalse(strpos($content, '<body'), 'The response is not a rendered page.');

        return $content;
    }
}
