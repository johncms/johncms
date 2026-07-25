<?php

declare(strict_types=1);

namespace Tests\Unit;

use InvalidArgumentException;
use Johncms\Exceptions\HttpRedirectException;
use Johncms\Exceptions\PageNotFoundException;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the control-flow helpers (plan stage 2a).
 *
 * They used to send headers and call exit; now they throw, so that building and sending
 * the response stays the job of the HTTP layer.
 *
 * The exception is captured into a variable and asserted after the try/catch instead of
 * asserting inside catch: a missing throw then fails on the assertion rather than being
 * reported as a risky test. Asserting right after the helper call is not an option — the
 * helpers are declared never-returning, so static analysis rejects that code as unreachable.
 */
final class HelpersTest extends TestCase
{
    private ?string $originalRequestUri = null;

    protected function setUp(): void
    {
        $this->originalRequestUri = $_SERVER['REQUEST_URI'] ?? null;
    }

    protected function tearDown(): void
    {
        if ($this->originalRequestUri === null) {
            unset($_SERVER['REQUEST_URI']);
            return;
        }

        $_SERVER['REQUEST_URI'] = $this->originalRequestUri;
    }

    public function testRedirectThrowsWithFoundStatusByDefault(): void
    {
        $caught = null;

        try {
            redirect('/forum/');
        } catch (HttpRedirectException $exception) {
            $caught = $exception;
        }

        self::assertInstanceOf(HttpRedirectException::class, $caught);
        self::assertSame('/forum/', $caught->getUrl());
        self::assertSame(302, $caught->getStatus());
    }

    public function testRedirectKeepsTheGivenStatus(): void
    {
        $caught = null;

        try {
            redirect('https://example.org/new/', 301);
        } catch (HttpRedirectException $exception) {
            $caught = $exception;
        }

        self::assertInstanceOf(HttpRedirectException::class, $caught);
        self::assertSame('https://example.org/new/', $caught->getUrl());
        self::assertSame(301, $caught->getStatus());
    }

    public function testRedirectRejectsAnEmptyUrl(): void
    {
        $this->expectException(InvalidArgumentException::class);

        redirect('');
    }

    public function testRedirectRejectsANonRedirectStatus(): void
    {
        $this->expectException(InvalidArgumentException::class);

        redirect('/forum/', 200);
    }

    public function testPageNotFoundThrowsWithDefaultTemplateAndEmptyTexts(): void
    {
        $_SERVER['REQUEST_URI'] = '/missing-page/';
        $caught = null;

        try {
            pageNotFound();
        } catch (PageNotFoundException $exception) {
            $caught = $exception;
        }

        self::assertInstanceOf(PageNotFoundException::class, $caught);
        self::assertSame('system::error/404', $caught->getTemplate());
        // Empty texts mean "use the translated defaults", which the HTTP layer applies.
        self::assertSame('', $caught->getTitle());
        self::assertSame('', $caught->getMessage());
    }

    public function testPageNotFoundKeepsTheGivenTemplateTitleAndMessage(): void
    {
        $_SERVER['REQUEST_URI'] = '/missing-page/';
        $caught = null;

        try {
            pageNotFound('system::pages/result', 'Custom title', 'Custom message');
        } catch (PageNotFoundException $exception) {
            $caught = $exception;
        }

        self::assertInstanceOf(PageNotFoundException::class, $caught);
        self::assertSame('system::pages/result', $caught->getTemplate());
        self::assertSame('Custom title', $caught->getTitle());
        self::assertSame('Custom message', $caught->getMessage());
    }

    public function testCheckRedirectDoesNothingForAnUnlistedUri(): void
    {
        $_SERVER['REQUEST_URI'] = '/some/uri/that/is/not/in/the/redirect/map/';
        $this->expectNotToPerformAssertions();

        checkRedirect();
    }

    public function testCheckRedirectToleratesAMissingRequestUri(): void
    {
        unset($_SERVER['REQUEST_URI']);
        $this->expectNotToPerformAssertions();

        checkRedirect();
    }
}
