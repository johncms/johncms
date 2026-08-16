<?php

declare(strict_types=1);

namespace Tests\Unit\Auth\Authorization;

use Johncms\Auth\Authorization\Vote;
use Johncms\Auth\Authorization\Voters\ImpersonationVoter;
use Johncms\Auth\AuthMethod;
use Johncms\Auth\Identity;
use Johncms\Auth\Impersonation\ImpersonationSettings;
use PHPUnit\Framework\TestCase;
use Tests\Support\IdentityFactory;

final class ImpersonationVoterTest extends TestCase
{
    public function testAnOrdinaryVisitorIsNotAffected(): void
    {
        $voter = $this->voter(['admin.access']);

        self::assertSame(Vote::Abstain, $voter->vote(IdentityFactory::superAdmin(), 'admin.access', null));
    }

    /**
     * A Deny rather than a missing Allow: it has to outrank what the roles of the account and of
     * the administrator both say.
     */
    public function testADeniedPermissionIsRefusedWhileBrowsingAsSomebody(): void
    {
        $voter = $this->voter(['admin.access']);

        self::assertSame(Vote::Deny, $voter->vote($this->impersonated(), 'admin.access', null));
    }

    public function testAnythingElseIsLeftToTheOtherVoters(): void
    {
        $voter = $this->voter(['admin.access']);

        self::assertSame(Vote::Abstain, $voter->vote($this->impersonated(), 'forum.post', null));
    }

    /**
     * The list is configuration, so it accepts the patterns permissions are written in elsewhere.
     */
    public function testThePatternsOfThePermissionKeysApply(): void
    {
        $voter = $this->voter(['admin.*']);

        self::assertSame(Vote::Deny, $voter->vote($this->impersonated(), 'admin.roles.manage', null));
    }

    private function impersonated(): Identity
    {
        return new Identity(userId: 7, permissions: ['*'], method: AuthMethod::Session, impersonatorId: 1);
    }

    /**
     * @param list<string> $denied
     */
    private function voter(array $denied): ImpersonationVoter
    {
        return new ImpersonationVoter(new ImpersonationSettings(deniedPermissions: $denied));
    }
}
