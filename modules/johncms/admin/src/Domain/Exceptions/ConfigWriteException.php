<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Exceptions;

use RuntimeException;

/**
 * Бросается, когда не удалось записать локальный конфиг (system.local.php).
 */
final class ConfigWriteException extends RuntimeException
{
}
