<?php

declare(strict_types=1);

namespace Tests\Functional\Admin;

use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Auth\Authorization\Role;
use Johncms\Auth\Authorization\RolePermission;
use Johncms\Auth\Authorization\SystemRole;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\FunctionalUserFactory;

/**
 * The form of a role: its name and level, and the permissions switched on group by group.
 */
final class RoleFormTest extends FunctionalTestCase
{
    public function testThePermissionsAreSwitchedOnGroupByGroup(): void
    {
        $role = FunctionalUserFactory::createRole([CorePermissions::ADMIN_ACCESS], 20);

        $body = $this->page('/admin/roles/' . $role->id . '/edit');

        self::assertStringContainsString('data-permission-matrix', $body);
        self::assertStringContainsString('<span data-permission-total>1</span>', $body);
        self::assertMatchesRegularExpression(
            '/name="permissions\[\]" value="' . preg_quote(CorePermissions::ADMIN_ACCESS, '/') . '" data-permission\s+checked/',
            $body
        );
        self::assertStringContainsString('form="role-form"', $body, 'The head of the page saves the form.');
    }

    public function testABuiltInRoleShowsItsNameAndLevelAsFacts(): void
    {
        $user = Role::query()->where('slug', '=', SystemRole::User->value)->firstOrFail();

        $body = $this->page('/admin/roles/' . $user->id . '/edit');

        self::assertStringContainsString('class="admin-facts"', $body);
        self::assertStringNotContainsString('name="name"', $body);
        self::assertStringNotContainsString('name="level"', $body);
    }

    public function testARoleAllowedEverythingHasNothingToSave(): void
    {
        $supervisor = Role::query()->where('slug', '=', SystemRole::Supervisor->value)->firstOrFail();

        $body = $this->page('/admin/roles/' . $supervisor->id . '/edit');

        self::assertStringNotContainsString('data-permission-total', $body);
        self::assertStringNotContainsString('type="submit"', $body);
    }

    public function testANewRoleIsStoredWithWhatWasSwitchedOn(): void
    {
        $response = $this->postForm('/admin/roles/new', [
            'slug'        => 'form-test-editor',
            'name'        => 'Editor',
            'level'       => '25',
            'permissions' => [CorePermissions::ADMIN_ACCESS],
        ], FunctionalUserFactory::createSupervisor());

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        $role = Role::query()->where('slug', '=', 'form-test-editor')->firstOrFail();
        self::assertSame(25, $role->level);
        self::assertSame([CorePermissions::ADMIN_ACCESS], $this->permissionsOf($role));
    }

    public function testAFailedSaveKeepsWhatWasSwitchedOn(): void
    {
        $response = $this->postForm('/admin/roles/new', [
            'slug'        => 'Not a slug',
            'name'        => 'Editor',
            'level'       => '25',
            'permissions' => [CorePermissions::ADMIN_ACCESS],
        ], FunctionalUserFactory::createSupervisor());
        $body = (string) $response->getContent();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('is-invalid', $body);
        self::assertStringContainsString('<span data-permission-total>1</span>', $body);
    }

    public function testAnExistingRoleIsSaved(): void
    {
        $role = FunctionalUserFactory::createRole([CorePermissions::ADMIN_ACCESS], 20);

        $response = $this->postForm('/admin/roles/' . $role->id . '/edit', [
            'name'        => 'Renamed',
            'level'       => '30',
            'permissions' => [CorePermissions::USERS_NOTES_VIEW],
        ], FunctionalUserFactory::createSupervisor());

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame('/admin/roles', $response->headers->get('Location'));
        $role->refresh();
        self::assertSame('Renamed', $role->name);
        self::assertSame(30, $role->level);
        self::assertSame([CorePermissions::USERS_NOTES_VIEW], $this->permissionsOf($role));
    }

    public function testABuiltInRoleKeepsItsNameAndLevelButTakesItsPermissions(): void
    {
        $user = Role::query()->where('slug', '=', SystemRole::User->value)->firstOrFail();

        $this->postForm('/admin/roles/' . $user->id . '/edit', [
            'name'        => 'Renamed',
            'level'       => '5',
            'permissions' => [CorePermissions::ADMIN_ACCESS],
        ], FunctionalUserFactory::createSupervisor());

        $saved = Role::query()->findOrFail($user->id);
        self::assertSame($user->name, $saved->name);
        self::assertSame($user->level, $saved->level);
        self::assertSame([CorePermissions::ADMIN_ACCESS], $this->permissionsOf($saved));
    }

    public function testATakenIdentifierIsAnErrorOfItsField(): void
    {
        $taken = FunctionalUserFactory::createRole([], 20);

        $response = $this->postForm('/admin/roles/new', [
            'slug'  => $taken->slug,
            'name'  => 'Twin',
            'level' => '20',
        ], FunctionalUserFactory::createSupervisor());

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('is-invalid" id="role-slug"', (string) $response->getContent());
        self::assertSame(1, Role::query()->where('slug', '=', $taken->slug)->count());
    }

    public function testARoleAboveTheVisitorIsNeitherOpenedNorChanged(): void
    {
        $above = FunctionalUserFactory::createRole([], 50);

        $response = $this->handleRequest('/admin/roles/' . $above->id . '/edit', cookies: $this->actingAs($this->manager()));
        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());

        $response = $this->postForm('/admin/roles/' . $above->id . '/edit', ['name' => 'Taken over', 'level' => '20'], $this->manager());
        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertSame($above->name, Role::query()->findOrFail($above->id)->name);
    }

    public function testARoleIsNotPlacedAboveTheVisitor(): void
    {
        $role = FunctionalUserFactory::createRole([], 10);

        $response = $this->postForm('/admin/roles/' . $role->id . '/edit', ['name' => 'Promoted', 'level' => '90'], $this->manager());

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertSame(10, Role::query()->findOrFail($role->id)->level);
    }

    public function testARoleThatIsGoneLeadsBackToTheList(): void
    {
        $response = $this->handleRequest('/admin/roles/999999/edit', cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()));

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame('/admin/roles', $response->headers->get('Location'));
    }

    /**
     * @return list<string>
     */
    private function permissionsOf(Role $role): array
    {
        return RolePermission::query()->where('role_id', '=', $role->id)->orderBy('permission')->pluck('permission')->all();
    }

    private function manager(): User
    {
        return FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS, CorePermissions::ADMIN_ROLES_MANAGE], level: 20);
    }

    private function page(string $url): string
    {
        $response = $this->handleRequest($url, cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()));
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        return (string) $response->getContent();
    }
}
