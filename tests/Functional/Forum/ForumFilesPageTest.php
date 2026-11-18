<?php

declare(strict_types=1);

namespace Tests\Functional\Forum;

use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * The list of the files of a kind shows what its count says: a hidden file to whoever sees the
 * hidden posts, and to nobody else.
 */
final class ForumFilesPageTest extends FunctionalTestCase
{
    public function testAHiddenFileIsListedOnlyForWhoeverSeesTheHidden(): void
    {
        $author = FunctionalUserFactory::create(['name' => 'Author']);
        $section = Fixture::insert('forum_sections', ['name' => 'Talk', 'slug' => 'talk', 'parent' => 0, 'description' => '', 'section_type' => 1]);
        $topic = Fixture::insert('forum_topic', ['section_id' => $section, 'name' => 'A topic', 'slug' => 'a-topic', 'user_id' => $author->id, 'user_name' => 'Author', 'deleted' => 0, 'closed' => 0, 'pinned' => 0]);
        $post = Fixture::insert('forum_messages', ['topic_id' => $topic, 'user_id' => $author->id, 'user_name' => 'Author', 'date' => time(), 'text' => 'With a picture', 'deleted' => 1, 'deleted_by' => 'Alex']);
        Fixture::insert('cms_forum_files', ['cat' => 0, 'subcat' => $section, 'topic' => $topic, 'post' => $post, 'time' => time(), 'filename' => 'hidden-picture.jpg', 'filetype' => 5, 'del' => 1]);

        $moderator = (string) $this->handleRequest('/forum/files/?do=5', cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()))->getContent();
        $member = (string) $this->handleRequest('/forum/files/?do=5', cookies: $this->actingAs(FunctionalUserFactory::create()))->getContent();

        // The file as a row of the list: the staff also see the queries of the page, with the name in them.
        self::assertStringContainsString('>hidden-picture.jpg</a>', $moderator);
        self::assertStringNotContainsString('>hidden-picture.jpg</a>', $member);
    }
}
