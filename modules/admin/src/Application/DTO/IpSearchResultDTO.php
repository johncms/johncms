<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\DTO;

use Illuminate\Database\Eloquent\Collection;

final readonly class IpSearchResultDTO
{
    /**
     * @param Collection<int, \Johncms\Users\User>|null $users Найденные пользователи или null (пустой/ошибочный запрос)
     * @param list<string> $errors Ошибки разбора запроса
     */
    public function __construct(
        public ?Collection $users,
        public array $errors = [],
    ) {
    }
}
