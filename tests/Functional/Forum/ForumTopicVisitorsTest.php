<?php

declare(strict_types=1);

namespace Tests\Functional\Forum;

use Johncms\Modules\Forum\Domain\Repository\ForumWhoRepositoryInterface;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\FunctionalUserFactory;

/**
 * Who is in a topic is decided by the address each member was last seen at, in the query itself:
 * any page of the topic and its address before the paths count; a section or another topic whose
 * address ends in the same number does not.
 */
final class ForumTopicVisitorsTest extends FunctionalTestCase
{
    public function testTheMembersOnThePagesOfTheTopicAreCountedAndListed(): void
    {
        $in = [
            'Alpha' => '/forum/talk/a-topic-12/',
            'Bravo' => '/forum/talk/a-topic-12/?page=3',
            'Charlie' => '/forum?type=topic&id=12',
            'Delta' => '/forum/talk/a-topic-12',
        ];
        $out = [
            'Echo' => '/forum/talk-12/another-topic-7/',
            'Foxtrot' => '/forum/talk/a-topic-112/',
            'Golf' => '/forum?type=topic&id=123',
            'Hotel' => '/news/a-topic-12/',
        ];
        foreach ($in + $out as $name => $place) {
            FunctionalUserFactory::create(['name' => $name, 'lastdate' => time(), 'place' => $place]);
        }
        $who = $this->container()->get(ForumWhoRepositoryInterface::class);

        self::assertSame(4, $who->countTopicUsers(12));
        self::assertSame(['Alpha', 'Bravo'], $who->getTopicUsers(12, 0, 2)->pluck('name')->all(), 'Paged by the query, by name.');
        self::assertSame(['Charlie', 'Delta'], $who->getTopicUsers(12, 2, 2)->pluck('name')->all());
    }
}
