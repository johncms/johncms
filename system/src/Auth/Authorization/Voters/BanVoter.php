<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth\Authorization\Voters;

use Johncms\Auth\Authorization\AccessVoterInterface;
use Johncms\Auth\Authorization\PermissionMatcher;
use Johncms\Auth\Authorization\PermissionResolver;
use Johncms\Auth\Authorization\RoleLevels;
use Johncms\Auth\Authorization\SystemRole;
use Johncms\Auth\Authorization\Vote;
use Johncms\Auth\Identity;
use Johncms\Users\Ban;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Takes away what a ban is for: the whole site under a full ban, the permissions of one section
 * under the ban of that section.
 *
 * A Deny rather than the absence of an Allow, which is the difference that matters: a ban has to
 * outrank whatever the roles say, so a moderator banned from the forum moderates nothing there.
 * What a guest may do — reading the sections, mostly — stays open under any ban, or a banned
 * member would see less of the site than somebody who never signed in. Whatever a section lets
 * a guest do, and whatever asks for no permission at all, the section refuses on its own.
 *
 * The supervisor keeps the way back into a site that SuperAdminVoter guarantees.
 */
final class BanVoter implements AccessVoterInterface, ResetInterface
{
    /**
     * The permissions each kind of ban takes away, by the kinds the ban form offers. The private
     * messages ask for no permission, so their ban is the business of the mail alone.
     */
    private const array TAKES_AWAY = [
        1  => ['*'],
        10 => ['system.comments.*', 'news.comments.*'],
        11 => ['forum.*'],
        13 => ['guestbook.*'],
        15 => ['library.*'],
    ];

    /** @var array<int, list<int>> Answered once per request; the identity does not change within one. */
    private array $kinds = [];

    private ?Identity $guest = null;

    public function __construct(
        private readonly PermissionResolver $permissionResolver,
        private readonly RoleLevels $roleLevels,
    ) {
    }

    public function supports(string $permission, mixed $subject): bool
    {
        return true;
    }

    public function vote(Identity $identity, string $permission, mixed $subject): Vote
    {
        if ($identity->isGuest()) {
            return Vote::Abstain;
        }

        $kinds = $this->activeKinds($identity->userId);

        if (
            $kinds === []
            || SystemRole::grantsEverything($this->roleLevels->highest($identity))
            || $this->guest()->hasPermission($permission)
        ) {
            return Vote::Abstain;
        }

        foreach ($kinds as $kind) {
            if (PermissionMatcher::matchesAny(self::TAKES_AWAY[$kind] ?? [], $permission)) {
                return Vote::Deny;
            }
        }

        return Vote::Abstain;
    }

    public function reset(): void
    {
        $this->kinds = [];
        $this->guest = null;
    }

    /**
     * @return list<int>
     */
    private function activeKinds(int $userId): array
    {
        return $this->kinds[$userId] ??= Ban::query()
            ->where('user_id', '=', $userId)
            ->where('ban_time', '>', time())
            ->distinct()
            ->pluck('ban_type')
            ->map(static fn (mixed $kind): int => (int) $kind)
            ->values()
            ->all();
    }

    private function guest(): Identity
    {
        return $this->guest ??= $this->permissionResolver->resolve(Identity::guest());
    }
}
