<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Storage;

/**
 * The configured driver cannot be used: an unknown name, a missing package, or a setting the
 * driver needs and did not get. Thrown while the disk is being built, so the message has to
 * say what to change in the configuration.
 */
final class UnsupportedStorageDriverException extends StorageException
{
}
