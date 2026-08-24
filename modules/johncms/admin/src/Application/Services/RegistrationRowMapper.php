<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Services;

use Illuminate\Support\Collection;
use Johncms\Users\User;

/**
 * Строка пользователя, ожидающего подтверждения регистрации: данные admin::user_row
 * плюс сырой integer-IP для действия «удалить все регистрации с этим IP».
 */
final readonly class RegistrationRowMapper
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
        $row['ip_int'] = (int) $user->getRawOriginal('ip');

        return $row;
    }
}
