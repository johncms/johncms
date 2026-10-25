<?php

declare(strict_types=1);

namespace Tests\Functional\Admin;

use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Auth\Authorization\Role;
use Johncms\Auth\Authorization\SystemRole;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\FunctionalUserFactory;

/**
 * The list of roles: who holds each of them leads to the list of users, a role above the visitor
 * is listed but not opened, and a role is deleted from a dialog.
 */
final class RolesPageTest extends FunctionalTestCase
{
    public function testTheHoldersOfARoleLeadToTheListOfUsers(): void
    {
        $role = FunctionalUserFactory::createRole([], 20);
        FunctionalUserFactory::grant(FunctionalUserFactory::create()->id, $role->slug);

        $body = $this->page(FunctionalUserFactory::createSupervisor());

        self::assertStringContainsString('href="/admin/users?role=' . $role->id . '"', $body);
        self::assertStringContainsString('href="/admin/roles/' . $role->id . '/delete" data-admin-modal', $body);
    }

    public function testARoleAboveTheVisitorIsListedButNotOpened(): void
    {
        $manager = FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS, CorePermissions::ADMIN_ROLES_MANAGE], level: 20);
        $supervisor = $this->systemRole(SystemRole::Supervisor);

        $body = $this->page($manager);

        self::assertStringContainsString($supervisor->display_name, $body);
        self::assertStringNotContainsString('/admin/roles/' . $supervisor->id . '/edit', $body);
    }

    public function testTheModalIsAnsweredWithTheDialogAlone(): void
    {
        $role = FunctionalUserFactory::createRole([], 20);
        FunctionalUserFactory::grant(FunctionalUserFactory::create()->id, $role->slug);

        $response = $this->handleRequest(
            '/admin/roles/' . $role->id . '/delete',
            server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
            cookies: $this->actingAs(FunctionalUserFactory::createSupervisor())
        );
        $body = (string) $response->getContent();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringStartsWith('<form class="modal-content" method="post" action="/admin/roles/' . $role->id . '/delete">', trim($body));
        self::assertStringContainsString($role->name, $body);
        self::assertStringNotContainsString('<html', $body);
    }

    public function testAPlainVisitGetsTheDialogAsAPage(): void
    {
        $role = FunctionalUserFactory::createRole([], 20);

        $response = $this->handleRequest('/admin/roles/' . $role->id . '/delete', cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()));
        $body = (string) $response->getContent();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('class="modal admin-dialog-page"', $body);
        self::assertStringContainsString('href="/admin/roles" class="btn-close"', $body);
    }

    public function testABuiltInRoleIsNotOfferedForDeletion(): void
    {
        $user = $this->systemRole(SystemRole::User);

        $response = $this->handleRequest(
            '/admin/roles/' . $user->id . '/delete',
            server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
            cookies: $this->actingAs(FunctionalUserFactory::createSupervisor())
        );

        self::assertStringNotContainsString('<form', (string) $response->getContent());
        self::assertStringContainsString('alert-danger', (string) $response->getContent());
    }

    public function testTheDialogDeletesTheRole(): void
    {
        $role = FunctionalUserFactory::createRole([], 20);

        $response = $this->postForm('/admin/roles/' . $role->id . '/delete', ['back' => '/admin/roles'], FunctionalUserFactory::createSupervisor());

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame('/admin/roles', $response->headers->get('Location'));
        self::assertNull(Role::query()->find($role->id));
    }

    private function page(User $visitor): string
    {
        $response = $this->handleRequest('/admin/roles', cookies: $this->actingAs($visitor));
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        return (string) $response->getContent();
    }

    private function systemRole(SystemRole $role): Role
    {
        return Role::query()->where('slug', '=', $role->value)->firstOrFail();
    }
}
