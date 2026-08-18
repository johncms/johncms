<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Files;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Johncms\Casts\DateHuman;

/**
 * One row of the file registry: what was stored, on which disk, and under which path.
 *
 * A detail of this package. Modules get StoredFileDTO out of FileStore instead, because a model
 * is an open door — `$file->delete()` or a reassigned `path` would leave the row and the file on
 * the disk disagreeing, and keeping those two in step is the whole reason FileStore exists.
 *
 * @mixin Builder
 * @property int $id
 * @property string $storage Name of the disk the file is on, as configured in filesystem.*.php.
 * @property string $name    Name the file was uploaded under; shown to visitors, never used as a path.
 * @property string $path    Path on the disk, built from the hash of the contents.
 * @property int $size
 * @property string $md5
 * @property string $sha1
 *
 * @property array $created_at - Creation date
 * @property array $updated_at - Date of change
 */
class StoredFile extends Model
{
    protected $table = 'files';

    protected $dateFormat = 'Y-m-d H:i:s';

    protected $casts = [
        'created_at' => DateHuman::class,
        'updated_at' => DateHuman::class,
    ];

    protected $fillable = [
        'storage',
        'name',
        'path',
        'size',
        'md5',
        'sha1',
    ];
}
