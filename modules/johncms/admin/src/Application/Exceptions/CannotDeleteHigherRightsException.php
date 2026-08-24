<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Exceptions;

use RuntimeException;

/**
 * Нельзя удалить пользователя с правами выше своих.
 */
final class CannotDeleteHigherRightsException extends RuntimeException
{
}
