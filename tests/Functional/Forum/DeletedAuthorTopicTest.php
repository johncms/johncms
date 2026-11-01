<?php

declare(strict_types=1);

namespace Tests\Functional\Forum;

use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;

final class DeletedAuthorTopicTest extends FunctionalTestCase
{
    public function testATopicCanRenderAMessageWhoseAuthorNoLongerExists(): void
    {
        $section = Fixture::insert('forum_sections', [
            'name' => 'Talk',
            'slug' => 'talk',
            'section_type' => 1,
        ]);
        $topic = Fixture::insert('forum_topic', [
            'section_id' => $section,
            'name' => 'A topic',
            'slug' => 'a-topic',
        ]);
        Fixture::insert('forum_messages', [
            'topic_id' => $topic,
            'user_id' => 0,
            'user_name' => 'Former author',
            'date' => time(),
            'text' => 'A message from a deleted account',
        ]);

        $response = $this->handleRequest(
            '/forum/talk/a-topic-' . $topic . '/',
            catch: false,
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('Former author', (string) $response->getContent());
        self::assertStringContainsString('A message from a deleted account', (string) $response->getContent());
    }
}
