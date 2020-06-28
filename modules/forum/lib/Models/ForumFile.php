<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Forum\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Johncms\FileInfo;
use Johncms\System\Users\User;

/**
 * Class File
 *
 * @package Forum\Models
 *
 * @mixin Builder
 * @property int $id
 * @property int $cat
 * @property int $subcat
 * @property int $topic
 * @property int $post
 * @property int $time
 * @property string $filename
 * @property int $filetype
 * @property int $dlcount
 * @property bool $del
 *
 * @property FileInfo|null $file_attrs
 * @property string $file_url
 * @property string $delete_url
 * @property string $file_preview
 * @property string $file_size
 */
class ForumFile extends Model
{
    protected $table = 'cms_forum_files';

    public $timestamps = false;

    /** @var null|FileInfo */
    public $file_info = null;

    protected $fillable = [
        'cat',
        'subcat',
        'topic',
        'post',
        'time',
        'filename',
        'filetype',
        'dlcount',
        'del',
    ];

    protected $appends = [
        'file_url',
        'file_attrs',
        'delete_url',
    ];

    /**
     * Добавляем глобальные ограничения
     *
     * @return void
     */
    protected static function boot(): void
    {
        parent::boot();

        static::addGlobalScope(
            'access',
            static function (Builder $builder) {
                /** @var User $user */
                $user = di(User::class);
                if ($user->rights < 7) {
                    $builder->where('del', '!=', 1);
                }
            }
        );
    }

    public function getFileInfo(): void
    {
        $this->file_info = new FileInfo(UPLOAD_PATH . 'forum/attach/' . $this->filename);
    }

    /**
     * Preview picture url
     *
     * @return string
     */
    public function getFilePreviewAttribute(): string
    {
        if (! is_object($this->file_info)) {
            $this->getFileInfo();
        }

        if (! $this->file_info->isFile()) {
            return '';
        }

        if ($this->file_info->isImage()) {
            return '/assets/modules/forum/thumbinal.php?file=' . (urlencode($this->filename));
        }
        return '';
    }

    /**
     * File size
     *
     * @return string
     */
    public function getFileSizeAttribute()
    {
        if (! is_object($this->file_info)) {
            $this->getFileInfo();
        }

        if (! $this->file_info->isFile()) {
            return '';
        }

        return format_size($this->file_info->getSize());
    }

    /**
     * Url to file download
     *
     * @return string
     */
    public function getFileUrlAttribute(): string
    {
        return '/forum/?act=file&amp;id=' . $this->id;
    }

    /**
     * Ip search page
     *
     * @return string
     */
    public function getDeleteUrlAttribute(): string
    {
        return '/forum/?act=editpost&amp;do=delfile&amp;fid=' . $this->id . '&amp;id=' . $this->post;
    }
}
