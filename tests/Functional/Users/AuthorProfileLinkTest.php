<?php

declare(strict_types=1);

namespace Tests\Functional\Users;

use Johncms\Users\User;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * The name of an author leads to the profile only for a visitor who may open it. The profiles are
 * open to members, so a guest — a crawler among them — is shown the name without a link instead
 * of an address that refuses them.
 */
final class AuthorProfileLinkTest extends FunctionalTestCase
{
    public function testAGuestSeesTheAuthorOfAGuestbookEntryWithoutALink(): void
    {
        $author = FunctionalUserFactory::create(['name' => 'Bilbo']);
        Fixture::insert('guest', ['user_id' => $author->id, 'name' => 'Bilbo', 'text' => 'Hello', 'time' => time()]);

        self::assertStringNotContainsString($this->link($author), $this->page('/guestbook'));
        self::assertStringContainsString($this->link($author), $this->page('/guestbook', FunctionalUserFactory::create()));
    }

    public function testAMemberIsLedToTheirOwnProfileAsWell(): void
    {
        $author = FunctionalUserFactory::create(['name' => 'Bilbo']);
        Fixture::insert('guest', ['user_id' => $author->id, 'name' => 'Bilbo', 'text' => 'Hello', 'time' => time()]);

        self::assertStringContainsString($this->link($author), $this->page('/guestbook', $author));
    }

    public function testAGuestSeesTheVisitorsOnlineWithoutALink(): void
    {
        $member = FunctionalUserFactory::create(['lastdate' => time()]);

        self::assertStringNotContainsString($this->link($member), $this->page('/online'));
        self::assertStringContainsString($this->link($member), $this->page('/online', FunctionalUserFactory::create()));
    }

    private function link(User $user): string
    {
        return 'href="/profile/' . $user->id . '"';
    }

    private function page(string $uri, ?User $user = null): string
    {
        return (string) $this->handleRequest($uri, cookies: $user === null ? [] : $this->actingAs($user))->getContent();
    }
}
