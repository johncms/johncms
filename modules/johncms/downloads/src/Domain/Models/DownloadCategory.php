<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Domain\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int         $id
 * @property int         $refid    The parent category, 0 for the top level.
 * @property string      $dir      Folder on disk.
 * @property int         $sort
 * @property string      $name     Name of the folder on disk.
 * @property string|null $slug
 * @property string      $desc
 * @property int         $field    Whether the members may upload into the folder.
 * @property string      $text     Extensions the members may upload.
 * @property string      $rus_name Title shown on the site.
 * @property int         $total    Files inside, its subcategories included.
 */
final class DownloadCategory extends Model
{
    protected $table = 'download__category';
    public $timestamps = false;
    protected $fillable = ['refid', 'dir', 'sort', 'name', 'slug', 'desc', 'field', 'text', 'rus_name', 'total'];
}
