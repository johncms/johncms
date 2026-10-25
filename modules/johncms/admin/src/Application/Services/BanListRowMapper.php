<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Services;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Modules\Admin\Application\DTO\BanListEntryDTO;
use Johncms\Users\Bans\BanAccess;
use Johncms\Users\Bans\BanKind;
use Johncms\Utils\DurationFormatter;

/**
 * The rows of the ban panel, as admin::bans/results draws them: the account, the ban it is
 * listed for, its violations and what the visitor may do about them.
 */
final readonly class BanListRowMapper
{
    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private BanAccess $access,
    ) {
    }

    /**
     * @param list<BanListEntryDTO> $entries
     * @param string                $listUrl The page of the panel the rows are on, for a lifted
     *                                       ban to return to.
     * @return list<array<string, mixed>>
     */
    public function map(array $entries, string $listUrl, ?int $now = null): array
    {
        $now ??= time();
        // Who gave a ban is of interest to whoever gives them.
        $maySeeAuthor = $this->access->mayBanAnything();
        // Whether this particular account stands low enough is asked by the dialog: answering it
        // here would cost a query about the roles of every row.
        $mayLift = $this->accessChecker->allows(CorePermissions::USERS_BAN_PERMANENT);

        $rows = [];
        foreach ($entries as $entry) {
            $user = $entry->user;
            $ban = $entry->ban;
            $active = $ban->ban_time > $now;
            $bansUrl = '/admin/users/' . $user->id . '/bans';
            $actions = [];

            if ($mayLift && $active) {
                $actions[] = ['method' => 'get', 'url' => $bansUrl . '/' . $ban->id . '/lift?back=' . rawurlencode($listUrl), 'title' => __('Lift the ban'), 'icon' => 'undo-2', 'danger' => false, 'dialog' => true, 'fields' => []];
            }

            $actions[] = ['method' => 'get', 'url' => $bansUrl, 'title' => __('Violations history'), 'icon' => 'history', 'danger' => false, 'dialog' => false, 'fields' => []];
            $actions[] = ['method' => 'get', 'url' => '/admin/users/' . $user->id, 'title' => __('Profile'), 'icon' => 'user', 'danger' => false, 'dialog' => false, 'fields' => []];

            $rows[] = [
                'id'         => $user->id,
                'name'       => $user->name,
                'url'        => '/admin/users/' . $user->id,
                'online'     => (bool) $user->is_online,
                'kind'       => BanKind::tryFrom((int) $ban->ban_type)?->title() ?? (string) $ban->ban_type,
                'active'     => $active,
                'ends'       => (int) $ban->ban_time,
                'remains'    => $active ? DurationFormatter::format($ban->ban_time - $now) : '',
                'started'    => (int) $ban->ban_while,
                'reason'     => (string) $ban->ban_reason,
                'author'     => $maySeeAuthor ? (string) $ban->ban_who : '',
                'violations' => $entry->violations,
                'bans_url'   => $bansUrl,
                'actions'    => $actions,
            ];
        }

        return $rows;
    }
}
