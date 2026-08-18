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
 * What a disk is built on, chosen by config/autoload/filesystem.*.php.
 *
 * A name that is not listed here is refused while the settings are read, rather than quietly
 * treated as a local directory — the mistake the previous implementation made, where every
 * unknown type fell through to the local adapter.
 */
enum StorageDriver: string
{
    /** A directory on the server the CMS runs on. The default, and the only one a fresh site needs. */
    case Local = 'local';

    /**
     * An S3-compatible object store — AWS, or any of the services speaking its protocol.
     *
     * Needs league/flysystem-aws-s3-v3, which is not a dependency of the CMS: it pulls the AWS
     * SDK behind it, and a site that keeps its files on its own server has no use for either.
     */
    case S3 = 's3';
}
