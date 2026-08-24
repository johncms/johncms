<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Services;

use Illuminate\Support\Collection;
use Johncms\Users\User;

/**
 * Преобразует забаненного пользователя в строку для admin::user_row,
 * дополняя её данными бана (подсветка активного бана и кнопка истории нарушений).
 */
final readonly class BanListRowMapper
{
    public function __construct(
        private AdminUserRowMapper $userRowMapper,
    ) {
    }

    /**
     * @param Collection<int, User> $users
     * @return array<int, array<string, mixed>>
     */
    public function mapMany(Collection $users): array
    {
        return $users->map(fn (User $user): array => $this->map($user))->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function map(User $user): array
    {
        $row = $this->userRowMapper->map($user);
        $row['active'] = (int) $user->bantime > time();
        $row['buttons'] = [
            [
                'url'  => '/profile/' . $user->id . '/bans',
                'name' => __('Violations history') . ' (' . (int) $user->bancount . ')',
            ],
        ];

        return $row;
    }
}
