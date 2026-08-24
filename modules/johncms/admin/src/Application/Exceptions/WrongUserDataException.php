<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Exceptions;

use RuntimeException;

/**
 * Некорректные данные для удаления (пустой/собственный id).
 */
final class WrongUserDataException extends RuntimeException
{
}
