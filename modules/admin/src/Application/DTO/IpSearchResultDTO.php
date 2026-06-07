<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\DTO;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class IpSearchResultDTO
{
    /**
     * @param LengthAwarePaginator|null $users Найденные пользователи или null (пустой/ошибочный запрос)
     * @param list<string> $errors Ошибки разбора запроса
     */
    public function __construct(
        public ?LengthAwarePaginator $users,
        public array $errors = [],
    ) {
    }

    public function total(): int
    {
        return $this->users?->total() ?? 0;
    }
}
