<?php

declare(strict_types=1);

namespace Johncms\Cache;

use RuntimeException;

/**
 * The configured cache driver cannot be used: an unknown name, or an extension the server does
 * not have. Thrown while the pool is being built, so the message has to say what to change.
 */
final class UnsupportedCacheDriverException extends RuntimeException
{
}
