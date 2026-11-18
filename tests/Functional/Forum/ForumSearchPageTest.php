<?php

declare(strict_types=1);

namespace Tests\Functional\Forum;

use Tests\Functional\FunctionalTestCase;

/**
 * The search page says what it takes before anything is searched for, and a query it does not take
 * comes back in its field with the reason, not on a page of its own.
 */
final class ForumSearchPageTest extends FunctionalTestCase
{
    public function testAQueryTooShortComesBackInItsField(): void
    {
        $response = $this->handleRequest('/forum/search/?search=abc');

        self::assertSame(422, $response->getStatusCode());
        self::assertStringContainsString('value="abc"', (string) $response->getContent());
        self::assertStringContainsString('id="forum-search-error"', (string) $response->getContent());
    }

    public function testThePageWithoutAQuerySaysWhatTheSearchTakes(): void
    {
        $response = $this->handleRequest('/forum/search/');

        self::assertSame(200, $response->getStatusCode());
        self::assertStringNotContainsString('id="forum-search-error"', (string) $response->getContent());
        self::assertStringContainsString('site-empty-state', (string) $response->getContent());
    }
}
