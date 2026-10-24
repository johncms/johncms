<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Services;

use Illuminate\Support\Collection;
use Johncms\Users\Ban;
use Johncms\Users\Bans\BanAccess;
use Johncms\Users\Bans\BanDurationUnit;
use Johncms\Users\Bans\BanKind;
use Johncms\Users\User;
use Johncms\Utils\DurationFormatter;

/**
 * The bans of an account as admin::user/bans lists them, with what the visitor may do to each:
 * lift one in force, delete the record of any — the same rules as on the site (BanAccess).
 */
final readonly class UserBansMapper
{
    public function __construct(
        private BanAccess $access,
    ) {
    }

    /**
     * @param Collection<int, Ban> $bans
     * @return list<array<string, mixed>>
     */
    public function rows(User $user, Collection $bans, ?int $now = null): array
    {
        $now ??= time();
        $base = '/admin/users/' . $user->id . '/bans/';
        $mayLift = $this->access->mayLift($user->id);
        $mayDestroy = $this->access->mayDestroy($user->id);
        // Who gave a ban is of interest to whoever gives them.
        $maySeeAuthor = $this->access->mayBanAnything();

        $rows = [];
        foreach ($bans as $ban) {
            $active = $ban->ban_time > $now;
            $period = $ban->ban_time - $ban->ban_while;
            $actions = [];

            if ($mayLift && $active) {
                $actions[] = ['method' => 'get', 'url' => $base . $ban->id . '/lift', 'title' => __('Lift the ban'), 'icon' => 'undo-2', 'danger' => false, 'dialog' => true, 'fields' => []];
            }

            if ($mayDestroy) {
                $actions[] = ['method' => 'get', 'url' => $base . $ban->id . '/delete', 'title' => __('Delete the record'), 'icon' => 'trash-2', 'danger' => true, 'dialog' => true, 'fields' => []];
            }

            $rows[] = [
                'kind'     => BanKind::tryFrom((int) $ban->ban_type)?->title() ?? (string) $ban->ban_type,
                'active'   => $active,
                'started'  => (int) $ban->ban_while,
                'ends'     => (int) $ban->ban_time,
                'length'   => $period >= BanDurationUnit::UNTIL_LIFTED->seconds(1) ? BanDurationUnit::UNTIL_LIFTED->title() : DurationFormatter::format($period),
                'remains'  => $active ? DurationFormatter::format($ban->ban_time - $now) : '',
                'reason'   => (string) $ban->ban_reason,
                // Why the ban ended before its time, when something other than a moderator ended
                // it: the amnesty writes its mark here.
                'end_note' => (string) $ban->ban_raz,
                'author'   => $maySeeAuthor ? (string) $ban->ban_who : '',
                'actions'  => $actions,
            ];
        }

        return $rows;
    }

    /**
     * Whether the visitor may give a ban, and to this account.
     */
    public function mayBan(User $user): bool
    {
        return $this->access->mayBanAnything() && $this->access->outranks($user->id);
    }

    public function mayClear(User $user): bool
    {
        return $this->access->mayDestroy($user->id);
    }
}
