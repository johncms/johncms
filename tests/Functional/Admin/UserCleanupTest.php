<?php

declare(strict_types=1);

namespace Tests\Functional\Admin;

use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Auth\Authorization\UserRole;
use Johncms\Users\IpHistory;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * The cleanup removes the accounts that registered long ago, have not been seen for months and
 * left next to nothing — and nothing else.
 */
final class UserCleanupTest extends FunctionalTestCase
{
    private const MONTH = 2592000;

    public function testThePageCountsTheAccountsItWouldRemove(): void
    {
        $this->forgotten();
        $this->forgotten();

        $response = $this->handleRequest('/admin/users/cleanup', cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('<span class="admin-stat__value">2</span>', (string) $response->getContent());
        self::assertStringContainsString('href="/admin/users/cleanup/confirm" data-admin-modal', (string) $response->getContent());
    }

    public function testTheModalGetsTheDialogThatPostsTheCleanup(): void
    {
        $this->forgotten();

        $response = $this->handleRequest(
            '/admin/users/cleanup/confirm',
            server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
            cookies: $this->actingAs(FunctionalUserFactory::createSupervisor())
        );

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringStartsWith('<form class="modal-content" method="post" action="/admin/users/cleanup">', trim((string) $response->getContent()));
    }

    public function testWithNobodyToRemoveTheDialogOffersNoDeletion(): void
    {
        $response = $this->handleRequest('/admin/users/cleanup/confirm', cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringNotContainsString('<form class="modal-content"', (string) $response->getContent());
    }

    public function testOnlyTheForgottenAccountsAreRemoved(): void
    {
        $forgotten = $this->forgotten();
        Fixture::insert('cms_users_iphistory', ['user_id' => $forgotten->id, 'ip' => 1, 'ip_via_proxy' => 0, 'time' => time() - self::MONTH * 7]);
        $wroteInTheForum = $this->forgotten(['postforum' => 1]);
        $registeredLately = $this->forgotten(['datereg' => time() - self::MONTH]);
        $seenLately = $this->forgotten(['lastdate' => time() - self::MONTH]);
        // Staff that has kept quiet is staff all the same.
        $quietModerator = $this->forgotten();
        FunctionalUserFactory::grant($quietModerator->id, FunctionalUserFactory::createRole([], 20)->slug);

        $response = $this->postForm('/admin/users/cleanup', [], FunctionalUserFactory::createSupervisor());

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertNull(User::query()->find($forgotten->id));
        self::assertSame(0, IpHistory::query()->where('user_id', $forgotten->id)->count());
        foreach ([$wroteInTheForum, $registeredLately, $seenLately, $quietModerator] as $kept) {
            self::assertNotNull(User::query()->find($kept->id), $kept->name . ' is not one the cleanup removes.');
        }
    }

    public function testAModeratorWhoseRoleRanOutIsNoLongerKept(): void
    {
        $former = $this->forgotten();
        FunctionalUserFactory::grant($former->id, FunctionalUserFactory::createRole([], 20)->slug);
        UserRole::query()->where('user_id', '=', $former->id)->update(['expires_at' => time() - 1]);

        $this->postForm('/admin/users/cleanup', [], FunctionalUserFactory::createSupervisor());

        self::assertNull(User::query()->find($former->id));
    }

    public function testAModeratorOfThePanelMayNeitherOpenNorRunTheCleanup(): void
    {
        $forgotten = $this->forgotten();
        $moderator = FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS], level: 60);

        $page = $this->handleRequest('/admin/users/cleanup', cookies: $this->actingAs($moderator));
        $run = $this->postForm('/admin/users/cleanup', [], $moderator);

        self::assertSame(Response::HTTP_FORBIDDEN, $page->getStatusCode());
        self::assertSame(Response::HTTP_FORBIDDEN, $run->getStatusCode());
        self::assertNotNull(User::query()->find($forgotten->id));
    }

    /**
     * An account registered seven months ago, last seen six months ago, that wrote nothing.
     *
     * @param array<string, mixed> $attributes
     */
    private function forgotten(array $attributes = []): User
    {
        return FunctionalUserFactory::create($attributes + [
            'datereg'   => time() - self::MONTH * 7,
            'lastdate'  => time() - self::MONTH * 6,
            'postforum' => 0,
            'postguest' => 0,
            'komm'      => 0,
        ]);
    }
}
