<?php

declare(strict_types=1);

namespace Tests\Functional\Forum;

use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * The filter by author and the lists of files have nothing of their own to index: they only point
 * at topics. Search engines are kept off them.
 */
final class ForumIndexingTest extends FunctionalTestCase
{
    public function testTheFilterIsNotIndexed(): void
    {
        $topic = $this->topic();

        $html = (string) $this->handleRequest('/forum/filter/' . $topic . '/')->getContent();

        self::assertStringContainsString('<meta name="robots" content="noindex,follow">', $html);
    }

    public function testTheTopicAsksNotToFollowTheLinkToTheFilter(): void
    {
        $topic = $this->topic();

        $html = (string) $this->handleRequest('/forum/talk/a-topic-' . $topic . '/')->getContent();

        self::assertStringContainsString('<a href="/forum/filter/' . $topic . '/" rel="nofollow">', $html);
    }

    public function testTheFilesOfATopicAreNotIndexed(): void
    {
        $topic = $this->topic();

        foreach (['/forum/files/?t=' . $topic, '/forum/files/?t=' . $topic . '&do=1'] as $uri) {
            $html = (string) $this->handleRequest($uri)->getContent();

            self::assertStringContainsString('<meta name="robots" content="noindex,follow">', $html, $uri);
        }
    }

    public function testTheTopicAsksNotToFollowTheLinkToItsFiles(): void
    {
        $topic = $this->topic();

        $html = (string) $this->handleRequest('/forum/talk/a-topic-' . $topic . '/')->getContent();

        self::assertStringContainsString('<a href="/forum/files/?t=' . $topic . '" class="pe-2" rel="nofollow">', $html);
    }

    private function topic(): int
    {
        $section = Fixture::insert('forum_sections', [
            'name'         => 'Talk',
            'slug'         => 'talk',
            'parent'       => 0,
            'section_type' => 1,
            'description'  => '',
        ]);
        $topic = Fixture::insert('forum_topic', ['section_id' => $section, 'name' => 'A topic', 'slug' => 'a-topic']);
        $author = FunctionalUserFactory::create();
        Fixture::insert('forum_messages', [
            'topic_id'  => $topic,
            'user_id'   => $author->id,
            'user_name' => $author->name,
            'date'      => time(),
            'text'      => 'Hello',
        ]);

        return $topic;
    }
}
