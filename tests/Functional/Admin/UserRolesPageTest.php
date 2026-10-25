<?php

declare(strict_types=1);

namespace Tests\Functional\Admin;

use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Auth\Authorization\UserRole;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\FunctionalUserFactory;

/**
 * The roles of one account, handed out and taken back on a page of their own, by whoever manages
 * the roles and only below their own place.
 */
final class UserRolesPageTest extends FunctionalTestCase
{
    public function testARoleIsGrantedUntilTheEndOfTheDayPicked(): void
    {
        $member = FunctionalUserFactory::create();
        $role = FunctionalUserFactory::createRole([], 20);

        $response = $this->save($member->id, ['roles' => [(string) $role->id], 'expires_' . $role->id => '2030-01-15']);

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        $grant = UserRole::query()->where('user_id', $member->id)->where('role_id', $role->id)->firstOrFail();
        self::assertSame(strtotime('2030-01-15 23:59:59'), (int) $grant->expires_at);
    }

    public function testARoleLeftUntickedIsTakenBack(): void
    {
        $member = FunctionalUserFactory::create();
        $role = FunctionalUserFactory::createRole([], 20);
        FunctionalUserFactory::grant($member->id, $role->slug);

        $this->save($member->id, []);

        self::assertSame(0, UserRole::query()->where('user_id', $member->id)->where('role_id', $role->id)->count());
    }

    public function testADateThatIsNoDateKeepsTheRolesAsTheyWere(): void
    {
        $member = FunctionalUserFactory::create();
        $role = FunctionalUserFactory::createRole([], 20);

        $response = $this->save($member->id, ['roles' => [(string) $role->id], 'expires_' . $role->id => 'next tuesday-ish']);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode(), 'The form comes back with what stopped it.');
        self::assertSame(0, UserRole::query()->where('user_id', $member->id)->count());
    }

    public function testTheRolesAreForWhoeverManagesThem(): void
    {
        $staff = FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS], level: 20);
        $member = FunctionalUserFactory::create();

        $response = $this->handleRequest('/admin/users/' . $member->id . '/roles', cookies: $this->actingAs($staff));

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testTheRolesOfSomebodyStandingAboveAreNotChanged(): void
    {
        $manager = FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS, CorePermissions::ADMIN_ROLES_MANAGE], level: 20);
        $supervisor = FunctionalUserFactory::createSupervisor();
        $before = UserRole::query()->where('user_id', $supervisor->id)->count();

        $response = $this->postForm('/admin/users/' . $supervisor->id . '/roles', [], $manager);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertSame($before, UserRole::query()->where('user_id', $supervisor->id)->count());
    }

    public function testThePageIsATabOfTheAccount(): void
    {
        $member = FunctionalUserFactory::create();
        $role = FunctionalUserFactory::createRole([], 20);
        FunctionalUserFactory::grant($member->id, $role->slug);
        UserRole::query()->where('user_id', $member->id)->update(['expires_at' => strtotime('2030-01-15 23:59:59')]);

        $body = $this->page($member->id, FunctionalUserFactory::createSupervisor());

        self::assertMatchesRegularExpression('#<a class="nav-link active" href="/admin/users/' . $member->id . '/roles"#', $body);
        self::assertMatchesRegularExpression('/name="roles\[\]" value="' . $role->id . '" id="role-' . $role->id . '"\s+checked/', $body);
        self::assertStringContainsString('name="expires_' . $role->id . '"', $body);
        self::assertStringContainsString('value="2030-01-15"', $body);
    }

    public function testAGrantThatRanOutIsMarked(): void
    {
        $member = FunctionalUserFactory::create();
        $role = FunctionalUserFactory::createRole([], 20);
        FunctionalUserFactory::grant($member->id, $role->slug);
        UserRole::query()->where('user_id', $member->id)->update(['expires_at' => time() - 86400]);

        $body = $this->page($member->id, FunctionalUserFactory::createSupervisor());

        self::assertStringContainsString('bg-danger-subtle text-danger-emphasis badge-dot', $body);
    }

    public function testARoleAboveTheVisitorIsShownLocked(): void
    {
        $manager = FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS, CorePermissions::ADMIN_ROLES_MANAGE], level: 20);
        $above = FunctionalUserFactory::createRole([], 50);
        $member = FunctionalUserFactory::create();

        $body = $this->page($member->id, $manager);

        self::assertMatchesRegularExpression('/value="' . $above->id . '" id="role-' . $above->id . '"\s+disabled/', $body);
        self::assertStringNotContainsString('name="expires_' . $above->id . '"', $body);
    }

    public function testTheTabIsOfferedToWhoeverManagesTheRoles(): void
    {
        $member = FunctionalUserFactory::create();
        $staff = FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS], level: 20);

        $forSupervisor = (string) $this->handleRequest('/admin/users/' . $member->id, cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()))->getContent();
        $staffResponse = $this->handleRequest('/admin/users/' . $member->id, cookies: $this->actingAs($staff));
        $forStaff = (string) $staffResponse->getContent();

        self::assertStringContainsString('class="nav-link" href="/admin/users/' . $member->id . '/roles"', $forSupervisor);
        self::assertSame(Response::HTTP_OK, $staffResponse->getStatusCode());
        self::assertStringNotContainsString('/admin/users/' . $member->id . '/roles', $forStaff);
    }

    private function page(int $userId, User $visitor): string
    {
        $response = $this->handleRequest('/admin/users/' . $userId . '/roles', cookies: $this->actingAs($visitor));
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        return (string) $response->getContent();
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function save(int $userId, array $parameters): Response
    {
        return $this->postForm('/admin/users/' . $userId . '/roles', $parameters, FunctionalUserFactory::createSupervisor());
    }
}
