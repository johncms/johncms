<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Enums;

/**
 * Режим поиска по IP: по текущим адресам пользователей или по истории IP.
 */
enum IpSearchMode: string
{
    case ACTUAL = 'actual';
    case HISTORY = 'history';
}
