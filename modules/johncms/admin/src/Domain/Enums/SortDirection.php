<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Enums;

enum SortDirection: string
{
    case ASC = 'asc';
    case DESC = 'desc';

    public function reverse(): self
    {
        return $this === self::ASC ? self::DESC : self::ASC;
    }
}
