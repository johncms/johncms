<?php

declare(strict_types=1);

namespace Tests\Functional\Forum;

use Johncms\Auth\Authorization\UserRole;
use Johncms\Modules\Forum\Domain\Repository\ForumMessageRepositoryInterface;
use Johncms\Users\User;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * A curator of a topic is appointed among those who wrote in it. Whoever holds a role in force
 * moderates the topic anyway and is not offered; once the grant runs out they are again.
 */
final class CuratorCandidatesTest extends FunctionalTestCase
{
    public function testAMemberWhoseRoleRanOutIsOfferedAgain(): void
    {
        $role = FunctionalUserFactory::createRole([], 30);
        $member = FunctionalUserFactory::create(['name' => 'Bilbo']);
        $moderator = FunctionalUserFactory::create(['name' => 'Gandalf']);
        $former = FunctionalUserFactory::create(['name' => 'Radagast']);
        FunctionalUserFactory::grant($moderator->id, $role->slug);
        FunctionalUserFactory::grant($former->id, $role->slug);
        UserRole::query()->where('user_id', '=', $former->id)->update(['expires_at' => time() - 1]);

        $topic = Fixture::insert('forum_topic', ['name' => 'Candidates', 'user_id' => $member->id]);
        foreach ([$member, $moderator, $former] as $author) {
            $this->writeIn($topic, $author);
        }

        $candidates = $this->container()->get(ForumMessageRepositoryInterface::class)->getTopicCuratorCandidates($topic);

        self::assertSame(['Bilbo', 'Radagast'], array_column($candidates, 'user_name'));
    }

    private function writeIn(int $topic, User $author): void
    {
        Fixture::insert('forum_messages', [
            'topic_id'  => $topic,
            'user_id'   => $author->id,
            'user_name' => $author->name,
            'date'      => time(),
            'text'      => 'Hello',
        ]);
    }
}
