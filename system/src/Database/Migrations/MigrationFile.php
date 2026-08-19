<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Database\Migrations;

/**
 * A migration as it lies on disk, before anything has been read out of it.
 *
 * Identity is the source and the version together, and that is what the journal stores. Not the
 * file name: two modules may well reach the same minute, and the words after the timestamp are a
 * description an author is free to correct.
 */
final readonly class MigrationFile
{
    public function __construct(
        public string $source,
        public string $version,
        public string $name,
        public string $path,
    ) {
    }

    public function id(): string
    {
        return $this->source . ':' . $this->version;
    }

    /**
     * What the file contained. Recorded when the migration is applied, so that editing it
     * afterwards can be pointed out later.
     */
    public function checksum(): ?string
    {
        $checksum = @sha1_file($this->path);

        return $checksum === false ? null : $checksum;
    }
}
