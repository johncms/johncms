<?php

declare(strict_types=1);

namespace Tests\Functional\Forum;

use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;

/**
 * The title and the description of a list of topics and of a topic name the page from the second
 * one onward.
 */
final class ForumPageMetaTest extends FunctionalTestCase
{
    public function testTheFirstPageOfASectionHasNoNumber(): void
    {
        $this->topic($this->section(), 1);

        $html = (string) $this->handleRequest('/forum/talk/')->getContent();

        self::assertStringContainsString('<title>Talk', $html);
        self::assertStringContainsString('<meta name="description" content="About talk">', $html);
        self::assertStringNotContainsString('Talk — ', $html);
    }

    public function testTheSecondPageOfASectionNamesItsNumber(): void
    {
        $section = $this->section();
        for ($i = 1; $i <= 3; $i++) {
            $this->topic($section, $i);
        }

        $html = $this->secondPage('/forum/talk/');

        self::assertStringContainsString('<title>Talk' . $this->suffix(), $html);
        self::assertStringContainsString('<meta name="description" content="About talk' . $this->suffix() . '">', $html);
    }

    public function testTheSecondPageOfATopicNamesItsNumber(): void
    {
        $topic = $this->topic($this->section(), 1, 'About the topic');
        for ($i = 1; $i <= 3; $i++) {
            Fixture::insert('forum_messages', ['topic_id' => $topic, 'user_name' => 'Author', 'date' => time(), 'text' => 'Post ' . $i]);
        }

        $html = $this->secondPage('/forum/talk/topic-1-' . $topic . '/');

        self::assertStringContainsString('<title>Topic 1' . $this->suffix(), $html);
        self::assertStringContainsString('<meta name="description" content="About the topic' . $this->suffix() . '">', $html);
    }

    private function secondPage(string $uri): string
    {
        return (string) $this->withConfig(
            ['johncms' => ['kmess' => 2]],
            fn() => $this->handleRequest($uri, parameters: ['page' => 2])->getContent()
        );
    }

    private function suffix(): string
    {
        return ' — ' . d__('system', 'Page') . ' 2';
    }

    private function section(): int
    {
        return Fixture::insert('forum_sections', [
            'name'             => 'Talk',
            'slug'             => 'talk',
            'parent'           => 0,
            'section_type'     => 1,
            'description'      => '',
            'meta_description' => 'About talk',
        ]);
    }

    private function topic(int $section, int $number, string $description = ''): int
    {
        return Fixture::insert('forum_topic', [
            'section_id'                => $section,
            'name'                      => 'Topic ' . $number,
            'slug'                      => 'topic-' . $number,
            'meta_description'          => $description,
            'user_name'                 => 'Author',
            'post_count'                => 1,
            'mod_post_count'            => 1,
            'last_post_date'            => time(),
            'mod_last_post_date'        => time(),
            'last_post_author_name'     => 'Author',
            'mod_last_post_author_name' => 'Author',
        ]);
    }
}
