<?php

declare(strict_types=1);

namespace Tests\Functional\Forum;

use Illuminate\Database\Capsule\Manager as Capsule;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * The paths of the sections are remembered for one request, not for the life of the process: a
 * section renamed between two requests leads to its new address in the second one.
 */
final class SectionPathTest extends FunctionalTestCase
{
    public function testARenamedSectionLeadsToItsNewAddressInTheNextRequest(): void
    {
        $section = Fixture::insert('forum_sections', ['name' => 'Talk', 'slug' => 'talk', 'parent' => 0, 'section_type' => 1]);
        $topic = Fixture::insert('forum_topic', ['section_id' => $section, 'name' => 'A topic', 'slug' => 'a-topic']);
        FunctionalUserFactory::create(['lastdate' => time(), 'place' => '/forum/talk/a-topic-' . $topic . '/']);

        self::assertStringContainsString('href="/forum/talk/a-topic-' . $topic . '/"', $this->online());

        Capsule::table('forum_sections')->where('id', $section)->update(['slug' => 'chat']);

        self::assertStringContainsString('href="/forum/chat/a-topic-' . $topic . '/"', $this->online());
    }

    private function online(): string
    {
        return (string) $this->handleRequest('/online')->getContent();
    }
}
