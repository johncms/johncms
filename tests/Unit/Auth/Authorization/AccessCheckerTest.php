<?php

declare(strict_types=1);

namespace Tests\Unit\Auth\Authorization;

use Johncms\Auth\Authentication\AuthenticatorChain;
use Johncms\Auth\Authorization\PermissionResolver;
use Johncms\Auth\Authorization\AccessChecker;
use Johncms\Auth\Authorization\AccessVoterInterface;
use Johncms\Auth\Authorization\Vote;
use Johncms\Auth\CurrentUser;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\HttpFoundation\RequestStack;
use Tests\Support\FakeAccessVoter;
use Tests\Support\FakeRoleRepository;
use Tests\Support\IdentityFactory;

final class AccessCheckerTest extends TestCase
{
    public function testRefusesWhenNobodyVotes(): void
    {
        self::assertFalse(
            $this->checker()->allowsFor(IdentityFactory::superAdmin(), 'forum.topic.delete')
        );
    }

    public function testAllowsOnASingleAllow(): void
    {
        $checker = $this->checker(new FakeAccessVoter(Vote::Allow));

        self::assertTrue($checker->allowsFor(IdentityFactory::user(), 'forum.topic.delete'));
    }

    public function testDenyOutranksAllowRegardlessOfOrder(): void
    {
        $denyLast = $this->checker(new FakeAccessVoter(Vote::Allow), new FakeAccessVoter(Vote::Deny));
        $denyFirst = $this->checker(new FakeAccessVoter(Vote::Deny), new FakeAccessVoter(Vote::Allow));

        self::assertFalse($denyLast->allowsFor(IdentityFactory::user(), 'forum.topic.delete'));
        self::assertFalse($denyFirst->allowsFor(IdentityFactory::user(), 'forum.topic.delete'));
    }

    public function testAbstainDecidesNothing(): void
    {
        $checker = $this->checker(new FakeAccessVoter(Vote::Abstain));

        self::assertFalse($checker->allowsFor(IdentityFactory::user(), 'forum.topic.delete'));
    }

    public function testVotersThatDoNotSupportThePermissionAreNotAsked(): void
    {
        $voter = new FakeAccessVoter(Vote::Allow, supports: false);

        self::assertFalse($this->checker($voter)->allowsFor(IdentityFactory::user(), 'forum.topic.delete'));
        self::assertFalse($voter->voted);
    }

    /**
     * A Deny has to be able to overrule an Allow that was already cast, which is only possible
     * if the chain keeps asking after the first Allow.
     */
    public function testEveryVoterIsAskedEvenAfterAnAllow(): void
    {
        $later = new FakeAccessVoter(Vote::Abstain);

        $this->checker(new FakeAccessVoter(Vote::Allow), $later)
            ->allowsFor(IdentityFactory::user(), 'forum.topic.delete');

        self::assertTrue($later->voted);
    }

    public function testSubjectReachesTheVoter(): void
    {
        $subject = new stdClass();
        $voter = new FakeAccessVoter(Vote::Allow);

        $this->checker($voter)->allowsFor(IdentityFactory::user(), 'forum.topic.delete', $subject);

        self::assertSame($subject, $voter->seenSubject);
    }

    public function testAllowsAsksAboutTheCurrentVisitor(): void
    {
        $voter = new FakeAccessVoter(Vote::Allow);

        // No request on the stack, so the current visitor is a guest — the point here is that
        // allows() reaches the chain at all, without an identity being passed in.
        self::assertTrue($this->checker($voter)->allows('forum.topic.view'));
    }

    private function checker(AccessVoterInterface ...$voters): AccessChecker
    {
        return new AccessChecker(
            new CurrentUser(new AuthenticatorChain([]), $this->permissionResolver(), new RequestStack()),
            $voters
        );
    }
    private function permissionResolver(): PermissionResolver
    {
        return new PermissionResolver(new FakeRoleRepository());
    }
}
