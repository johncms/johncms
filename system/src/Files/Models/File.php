<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Files\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Johncms\Casts\DateHuman;

/**
 * Class File
 *
 * @mixin Builder
 * @property int $id
 * @property string $storage
 * @property string $name
 * @property string $path
 * @property int $size
 * @property string $md5
 * @property string $sha1
 *
 * @property array $created_at - Creation date
 * @property array $updated_at - Date of change
 * @property string $url
 */
class File extends Model
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

    public function getUrlAttribute(): string
    {
        return Str::after(realpath(UPLOAD_PATH . $this->path), realpath(ROOT_PATH));
    }
}
