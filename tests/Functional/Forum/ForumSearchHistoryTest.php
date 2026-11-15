<?php

declare(strict_types=1);

namespace Tests\Functional\Forum;

use Johncms\Modules\Forum\Domain\Repository\ForumSearchHistoryRepositoryInterface;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\FunctionalUserFactory;

/**
 * The search history of a member is cleared from the dialog of the search page: opening the
 * address or a form the dialog did not confirm leaves it as it is.
 */
final class ForumSearchHistoryTest extends FunctionalTestCase
{
    public function testTheHistoryIsClearedOnlyByAConfirmedForm(): void
    {
        $member = FunctionalUserFactory::create();
        $history = $this->container()->get(ForumSearchHistoryRepositoryInterface::class);
        $history->saveForUser($member->id, ['johncms', 'forum']);

        $this->handleRequest('/forum/search/history/clear/', cookies: $this->actingAs($member));
        self::assertSame(['johncms', 'forum'], $history->getByUserId($member->id), 'Opening the address changes nothing.');

        self::assertSame(409, $this->postForm('/forum/search/history/clear/', [], $member)->getStatusCode());
        self::assertSame(['johncms', 'forum'], $history->getByUserId($member->id));

        $cleared = $this->postForm('/forum/search/history/clear/', ['confirmed' => '1'], $member);

        self::assertSame('/forum/search/', $cleared->headers->get('Location'));
        self::assertSame([], $history->getByUserId($member->id));
    }
}
