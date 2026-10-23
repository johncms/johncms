<?php

declare(strict_types=1);

namespace Tests\Functional\Online;

use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * Where a visitor is, as "who is online" says it: a place inside the forum by the name of the
 * topic, any other place by the dictionary of places.
 *
 * The assertions name data rather than words: the site answers in its own language, and what is
 * pinned here is which page a visitor is said to be on.
 */
final class VisitorPlacesTest extends FunctionalTestCase
{
    private const string TOPIC = 'An argument about tabs';

    private ?string $topicUrl = null;

    public function testAMemberInATopicOfTheForumIsShownThereByTheNameOfTheTopic(): void
    {
        FunctionalUserFactory::create(['lastdate' => time(), 'place' => $this->topicUrl()]);

        $page = (string) $this->handleRequest('/online')->getContent();

        self::assertStringContainsString(self::TOPIC, $page);
        self::assertStringContainsString('href="' . $this->topicUrl() . '"', $page);
    }

    public function testAMemberElsewhereIsShownByTheDictionaryOfPlaces(): void
    {
        FunctionalUserFactory::create(['lastdate' => time(), 'place' => '/news']);

        self::assertMatchesRegularExpression(
            '~<a href="[^"]*/news/">~',
            (string) $this->handleRequest('/online')->getContent()
        );
    }

    public function testTheHistoryShowsTheTopicAMemberWasLastIn(): void
    {
        FunctionalUserFactory::create(['lastdate' => time() - 3600, 'place' => $this->topicUrl()]);

        self::assertStringContainsString(self::TOPIC, (string) $this->handleRequest('/online/history')->getContent());
    }

    public function testTheStaffSeeTheTopicAGuestIsIn(): void
    {
        Fixture::insert('cms_sessions', [
            'session_id' => str_repeat('a', 32),
            'lastdate'   => time(),
            'sestime'    => time(),
            'place'      => $this->topicUrl(),
        ]);

        $page = $this->handleRequest('/online/guest', cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()));

        self::assertStringContainsString(self::TOPIC, (string) $page->getContent());
    }

    /**
     * A topic in a section of the forum, created on first use, and the address of it.
     */
    private function topicUrl(): string
    {
        if ($this->topicUrl !== null) {
            return $this->topicUrl;
        }

        $sectionId = Fixture::insert('forum_sections', [
            'name'         => 'Editors',
            'slug'         => 'editors',
            'parent'       => 0,
            'section_type' => 1,
        ]);
        $topicId = Fixture::insert('forum_topic', [
            'section_id' => $sectionId,
            'name'       => self::TOPIC,
            'slug'       => 'tabs',
        ]);

        return $this->topicUrl = '/forum/editors/tabs-' . $topicId . '/';
    }
}
