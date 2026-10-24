<?php

declare(strict_types=1);

namespace Tests\Functional\Homepage;

use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;

/**
 * The latest articles on the home page, as the settings of the news ask for them: how many, how
 * old at most, or none at all.
 */
final class LatestNewsTest extends FunctionalTestCase
{
    public function testTheLatestArticlesAreShownNewestFirst(): void
    {
        $this->article('Older', '-2 days');
        $this->article('Newest', '-1 hour');
        $this->article('Middle', '-1 day');

        self::assertSame(['Newest', 'Middle', 'Older'], $this->shown($this->homePage()));
    }

    public function testNoMoreArticlesThanTheSettingsAskFor(): void
    {
        $this->article('First', '-1 hour');
        $this->article('Second', '-2 hours');
        $this->article('Third', '-3 hours');
        $this->article('Fourth', '-4 hours');

        self::assertSame(['First', 'Second'], $this->shown($this->homePage(['homepage_quantity' => 2])));
    }

    public function testOnlyTheArticlesOfTheLastDaysWhenTheSettingsSaySo(): void
    {
        $this->article('Fresh', '-1 hour');
        $this->article('Stale', '-5 days');

        self::assertSame(['Fresh'], $this->shown($this->homePage(['homepage_days' => 2])));
    }

    public function testNoArticlesWhenTheBlockIsSwitchedOff(): void
    {
        $this->article('Fresh', '-1 hour');

        self::assertSame([], $this->shown($this->homePage(['homepage_show' => false])));
    }

    private function article(string $name, string $published): void
    {
        $time = date('Y-m-d H:i:s', strtotime($published));

        Fixture::insert('news_articles', [
            'name'        => $name,
            'code'        => strtolower($name),
            'active'      => 1,
            'active_from' => $time,
            'created_at'  => $time,
        ]);
    }

    /**
     * @param array<string, mixed> $news The settings of the news to answer with.
     */
    private function homePage(array $news = []): string
    {
        return $this->withConfig(['news' => $news], fn (): string => (string) $this->handleRequest('/')->getContent());
    }

    /**
     * @return list<string> The names of the articles on the page, in their order.
     */
    private function shown(string $page): array
    {
        preg_match_all('~<a class="h4 fw-bold" href="[^"]*">([^<]+)</a>~', $page, $matches);

        return $matches[1];
    }
}
