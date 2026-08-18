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
 * A disk was asked for by a name nothing is configured under.
 *
 * Most often a row of `files` written by an installation that had a disk this one does not:
 * the message lists what is configured, so the answer is either a fixed configuration or a
 * fixed row.
 */
final class UnknownStorageDiskException extends StorageException
{
}
