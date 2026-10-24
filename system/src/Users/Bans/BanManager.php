<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Users\Bans;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Auth\CurrentUser;
use Johncms\Users\Ban;
use Johncms\Users\Repository\BanRepositoryInterface;
use Johncms\Users\User;

/**
 * Giving, lifting and deleting the bans of an account — what the profile of the site and the
 * panel both do, by the same rules, and what neither may do without the other once a module is
 * switched off.
 *
 * Who may do what is BanAccess; this enforces it as well, so a form that skipped its guard still
 * cannot ban past what the visitor may.
 */
final readonly class BanManager
{
    public function __construct(
        private BanRepositoryInterface $bans,
        private BanAccess $access,
        private AccessCheckerInterface $accessChecker,
        private CurrentUser $currentUser,
    ) {
    }

    /**
     * Bans the account. A ban costs karma while the karma is on.
     *
     * @param int $kind   The kind of ban (BanKind), as the form sends it.
     * @param int $unit   What the length is counted in (BanDurationUnit), as the form sends it.
     * @param int $amount How many of those.
     *
     * @throws BanException What stopped it, said for the visitor.
     */
    public function apply(User $target, int $kind, int $unit, int $amount, string $reason, ?int $now = null): void
    {
        $now ??= time();
        $reason = trim($reason);
        if ($reason === '') {
            $reason = d__('system', 'Reason not specified');
        }

        $durationUnit = BanDurationUnit::tryFrom($unit);
        $error = null;

        if ($kind === 0 || $durationUnit === null || $amount < 1) {
            $error = d__('system', 'There is no required data');
        }

        if (! $this->access->mayApply($kind)) {
            $error = d__('system', 'You have no rights to ban in this section');
        }

        if (! $this->access->outranks($target->id)) {
            $error = d__('system', 'You do not have enought rights to ban this user');
        }

        if ($this->bans->countActiveByType($target->id, $kind) > 0) {
            $error = d__('system', 'Ban already active');
        }

        if ($error !== null || $durationUnit === null) {
            throw new BanException((string) $error);
        }

        $this->bans->add($target->id, $now + $this->duration($durationUnit, $amount), $now, $kind, $this->currentUser->user()->name, $reason);

        $karma = config('johncms')['karma'] ?? [];
        if (! empty($karma['on'])) {
            $this->bans->addKarmaPenalty($target->id, (int) $karma['karma_points'] * 2, $now, d__('system', 'System'), d__('system', 'Ban'));
        }
    }

    /**
     * @throws BanNotFoundException
     */
    public function find(int $userId, int $banId): Ban
    {
        return $this->bans->findForUser($banId, $userId) ?? throw new BanNotFoundException();
    }

    /**
     * A ban that is still in force: the only kind there is to lift.
     *
     * @throws BanNotFoundException
     * @throws BanException
     */
    public function findActive(int $userId, int $banId, ?int $now = null): Ban
    {
        $ban = $this->find($userId, $banId);

        if ($ban->ban_time < ($now ?? time())) {
            throw new BanException(d__('system', 'Ban not active'));
        }

        return $ban;
    }

    /**
     * Ends the ban now. The record stays in the history.
     */
    public function lift(Ban $ban, ?int $now = null): void
    {
        $this->bans->terminate($ban->id, $now ?? time());
    }

    /**
     * Deletes the record of the ban, and the karma penalty with it. A ban given while the karma was
     * off cost nothing, and the minuses the account has then are the votes of other members.
     */
    public function delete(Ban $ban): void
    {
        $penalty = $this->bans->findKarmaPenalty($ban->user_id, $ban->ban_while);
        if ($penalty !== null) {
            $this->bans->removeKarmaPenalty($penalty);
        }

        $this->bans->deleteById($ban->id);
    }

    /**
     * Deletes every ban of the account that has run out or was lifted, each the way deleting it
     * alone does. A ban still in force stays — ending it is lifting it, a step of its own.
     */
    public function clearHistory(int $userId, ?int $now = null): void
    {
        foreach ($this->bans->getFinishedForUser($userId, $now ?? time()) as $ban) {
            $this->delete($ban);
        }
    }

    /**
     * A ban of one section is worth a day; the general ban of an account is worth a month; longer
     * than that is for whoever may also lift one.
     */
    private function duration(BanDurationUnit $unit, int $amount): int
    {
        $seconds = $unit->seconds($amount);

        if (! $this->accessChecker->allows(CorePermissions::USERS_BAN_MANAGE) && $seconds > 86400) {
            $seconds = 86400;
        }

        if (! $this->accessChecker->allows(CorePermissions::USERS_BAN_PERMANENT) && $seconds > 2592000) {
            $seconds = 2592000;
        }

        return $seconds;
    }
}
