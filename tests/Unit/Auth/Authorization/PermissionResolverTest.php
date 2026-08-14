<?php

declare(strict_types=1);

namespace Tests\Unit\Auth\Authorization;

use Johncms\Auth\Authorization\PermissionResolver;
use Johncms\Auth\Identity;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeRoleRepository;
use Tests\Support\IdentityFactory;

final class PermissionResolverTest extends TestCase
{
    /**
     * Anonymous visitors have a role of their own; without it nothing would be open to them,
     * however the site is configured.
     */
    public function testAGuestGetsTheGuestRole(): void
    {
        $roles = new FakeRoleRepository();
        $roles->add('guest', ['forum.view'], isGuest: true);
        $roles->add('user', ['forum.post'], isDefault: true);

        $identity = (new PermissionResolver($roles))->resolve(Identity::guest());

        self::assertSame(['guest'], $identity->roles);
        self::assertTrue($identity->hasPermission('forum.view'));
        self::assertFalse($identity->hasPermission('forum.post'));
    }

    public function testASignedInVisitorGetsTheDefaultRoleWithoutARowOfTheirOwn(): void
    {
        $roles = new FakeRoleRepository();
        $roles->add('guest', ['forum.view'], isGuest: true);
        $roles->add('user', ['forum.post'], isDefault: true);

        $identity = (new PermissionResolver($roles))->resolve(IdentityFactory::user(id: 7));

        self::assertSame(['user'], $identity->roles);
        self::assertTrue($identity->hasPermission('forum.post'));
    }

    public function testGrantedRolesAddToTheDefaultOnes(): void
    {
        $roles = new FakeRoleRepository();
        $roles->add('user', ['forum.post'], isDefault: true);
        $roles->add('forum-moderator', ['forum.topic.delete']);
        $roles->grantTo(7, ['forum-moderator']);

        $identity = (new PermissionResolver($roles))->resolve(IdentityFactory::user(id: 7));

        self::assertSame(['user', 'forum-moderator'], $identity->roles);
        self::assertTrue($identity->hasPermission('forum.post'));
        self::assertTrue($identity->hasPermission('forum.topic.delete'));
    }

    /**
     * Whatever an authenticator happened to put there is replaced: what a visitor may do is
     * decided here, not by whoever recognised them.
     */
    public function testGrantsFromTheAuthenticatorAreNotTrusted(): void
    {
        $roles = new FakeRoleRepository();
        $roles->add('user', [], isDefault: true);

        $identity = (new PermissionResolver($roles))->resolve(
            IdentityFactory::withPermissions(['*'], id: 7)
        );

        self::assertFalse($identity->hasPermission('admin.access'));
    }

    public function testEverythingElseAboutTheIdentitySurvives(): void
    {
        $roles = new FakeRoleRepository();
        $roles->add('user', [], isDefault: true);

        $identity = (new PermissionResolver($roles))->resolve(
            IdentityFactory::impersonated(id: 7, impersonatorId: 9)
        );

        self::assertSame(7, $identity->userId);
        self::assertSame(9, $identity->impersonatorId);
        self::assertTrue($identity->isImpersonating());
    }
}
