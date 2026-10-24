<?php

declare(strict_types=1);

namespace Tests\Functional\Admin;

use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Users\BanKind;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * The page of one account in the panel stands on its own and shows only what the visitor may see.
 */
final class UserCardTest extends FunctionalTestCase
{
    public function testThePageShowsTheAccount(): void
    {
        $member = FunctionalUserFactory::create(['name' => 'Radagast', 'live' => 'Rhosgobel', 'postforum' => 17]);
        $this->ban($member->id);

        $body = $this->pageOf($member->id, FunctionalUserFactory::createSupervisor());

        self::assertStringContainsString('<h1 class="admin-page__title">Radagast</h1>', $body);
        self::assertStringContainsString('Rhosgobel', $body);
        self::assertStringContainsString('>17<', $body);
        self::assertStringContainsString('badge-dot">' . BanKind::FORUM->title() . '</span>', $body, 'The ban in force, by its kind.');
    }

    public function testAnUnknownAccountIsNotFound(): void
    {
        $response = $this->handleRequest('/admin/users/999999', cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()));

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testWhatIsKeptHiddenIsForWhoeverMayReadIt(): void
    {
        $member = FunctionalUserFactory::create([
            'mail'        => 'hidden@example.com',
            'mailvis'     => 0,
            'admin_notes' => 'Keeps arguing in the guestbook',
        ]);

        $moderator = FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS]);
        $forModerator = $this->pageOf($member->id, $moderator);

        self::assertStringNotContainsString('hidden@example.com', $forModerator);
        self::assertStringNotContainsString('Keeps arguing in the guestbook', $forModerator);
        self::assertStringNotContainsString('Others from this address', $forModerator);

        $forSupervisor = $this->pageOf($member->id, FunctionalUserFactory::createSupervisor());

        self::assertStringContainsString('hidden@example.com', $forSupervisor);
        self::assertStringContainsString('Keeps arguing in the guestbook', $forSupervisor);
        self::assertStringContainsString('Others from this address', $forSupervisor);
    }

    public function testTheListLeadsToThePageAndThePageToTheSite(): void
    {
        $member = FunctionalUserFactory::create(['name' => 'Beorn']);
        $supervisor = FunctionalUserFactory::createSupervisor();

        $list = (string) $this->handleRequest(
            '/admin/users?q=Beorn',
            server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
            cookies: $this->actingAs($supervisor)
        )->getContent();

        self::assertStringContainsString('href="/admin/users/' . $member->id . '">Beorn</a>', $list);
        // The profile module is switched on in the test site, so the site has a page to open.
        self::assertStringContainsString('href="/profile/' . $member->id . '"', $this->pageOf($member->id, $supervisor));
    }

    private function pageOf(int $id, User $visitor): string
    {
        $response = $this->handleRequest('/admin/users/' . $id, cookies: $this->actingAs($visitor));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        return (string) $response->getContent();
    }

    private function ban(int $userId): void
    {
        Fixture::insert(
            'cms_ban_users',
            ['user_id' => $userId, 'ban_time' => time() + 3600, 'ban_while' => time(), 'ban_type' => 11, 'ban_reason' => 'Flood']
        );
    }
}
