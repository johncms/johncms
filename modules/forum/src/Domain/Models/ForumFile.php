<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Modules\Forum\Domain\Models;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Modules\Forum\Application\Services\ForumPermissions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Johncms\FileInfo;
use Johncms\Users\User;

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
                if (! di(AccessCheckerInterface::class)->allows(ForumPermissions::DELETED_VIEW)) {
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
            return '/forum/file-preview/' . $this->id;
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
        return '/forum/download-file/' . $this->id . '/';
    }

    /**
     * Ip search page
     *
     * @return string
     */
    public function getDeleteUrlAttribute(): string
    {
        return '/forum/delete-post-file/' . $this->post . '/' . $this->id . '/';
    }
}
