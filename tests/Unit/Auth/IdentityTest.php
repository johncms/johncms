<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use Johncms\Auth\AuthMethod;
use Johncms\Auth\Identity;
use PHPUnit\Framework\TestCase;
use Tests\Support\IdentityFactory;

final class IdentityTest extends TestCase
{
    public function testGuestHasNothing(): void
    {
        $guest = Identity::guest();

        self::assertTrue($guest->isGuest());
        self::assertSame(0, $guest->userId);
        self::assertSame(AuthMethod::Guest, $guest->method);
        self::assertFalse($guest->isImpersonating());
        self::assertFalse($guest->hasPermission('forum.topic.view'));
    }

    public function testPermissionsHonourWildcards(): void
    {
        $identity = IdentityFactory::withPermissions(['forum.*', 'news.article.edit']);

        self::assertTrue($identity->hasPermission('forum.topic.delete'));
        self::assertTrue($identity->hasPermission('news.article.edit'));
        self::assertFalse($identity->hasPermission('news.article.delete'));
    }

    public function testRolesAreCheckedExactly(): void
    {
        $identity = IdentityFactory::withRoles(['user', 'forum-moderator']);

        self::assertTrue($identity->hasRole('forum-moderator'));
        self::assertFalse($identity->hasRole('admin'));
    }

    /**
     * A cookie request carries no token, so nothing narrows what the roles grant.
     */
    public function testWithoutATokenEverythingIsAllowedByTheToken(): void
    {
        self::assertTrue(IdentityFactory::user()->tokenAllows('forum.topic.delete'));
    }

    public function testTokenAbilitiesNarrowThePermissions(): void
    {
        $identity = IdentityFactory::token(permissions: ['*'], abilities: ['forum.*']);

        self::assertTrue($identity->hasPermission('news.article.delete'));
        self::assertTrue($identity->tokenAllows('forum.topic.delete'));
        self::assertFalse($identity->tokenAllows('news.article.delete'));
    }

    public function testTokenWithoutAbilitiesAllowsNothing(): void
    {
        $identity = IdentityFactory::token(permissions: ['*'], abilities: []);

        self::assertFalse($identity->tokenAllows('forum.topic.view'));
    }

    public function testImpersonationIsVisibleOnTheIdentity(): void
    {
        $identity = IdentityFactory::impersonated(id: 5, impersonatorId: 9);

        self::assertTrue($identity->isImpersonating());
        self::assertSame(9, $identity->impersonatorId);
        self::assertSame(5, $identity->userId);
        // Still a session request: impersonation is not a way of authenticating.
        self::assertSame(AuthMethod::Session, $identity->method);
    }
}
