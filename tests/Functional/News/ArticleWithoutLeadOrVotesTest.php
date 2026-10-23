<?php

declare(strict_types=1);

namespace Tests\Functional\News;

use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;

/**
 * An article nobody voted for and nobody wrote a lead to — most of them — on the pages that list
 * articles. Twig in debug mode treats an attribute of a model that is null as missing, and the
 * model answered such a question with an exception.
 */
final class ArticleWithoutLeadOrVotesTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Fixture::insert('news_articles', [
            'name'       => 'Nobody voted',
            'code'       => 'nobody-voted',
            'active'     => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function testTheHomePageListsIt(): void
    {
        $response = $this->handleRequest('/');

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('Nobody voted', (string) $response->getContent());
    }

    public function testTheNewsListIt(): void
    {
        $response = $this->handleRequest('/news/');

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('Nobody voted', (string) $response->getContent());
    }
}
