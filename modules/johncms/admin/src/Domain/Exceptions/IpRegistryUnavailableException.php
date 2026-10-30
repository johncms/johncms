<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Exceptions;

use RuntimeException;

/**
 * The registry could not be asked: no answer in time, the address of the server unreachable from
 * this hosting, an answer that is not what RDAP returns.
 */
final class IpRegistryUnavailableException extends RuntimeException
{
}
