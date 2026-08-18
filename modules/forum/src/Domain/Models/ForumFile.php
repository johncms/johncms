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
use Johncms\Users\User;
use Johncms\Modules\Forum\Infrastructure\Storage\ForumAttachmentStorage;

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
 * @property string $file_url
 * @property string $delete_url
 * @property string $file_preview
 * @property string $file_size
 */
class ForumFile extends Model
{
    protected $table = 'cms_forum_files';

    public $timestamps = false;

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

    public function exists(): bool
    {
        return di(ForumAttachmentStorage::class)->exists((string) $this->filename);
    }

    /**
     * Preview picture url
     *
     * @return string
     */
    public function getFilePreviewAttribute(): string
    {
        $attachments = di(ForumAttachmentStorage::class);
        $filename = (string) $this->filename;

        if (! $attachments->isImage($filename) || ! $attachments->exists($filename)) {
            return '';
        }

        return '/forum/file-preview/' . $this->id;
    }

    /**
     * File size
     *
     * @return string
     */
    public function getFileSizeAttribute()
    {
        $attachments = di(ForumAttachmentStorage::class);
        if (! $attachments->exists((string) $this->filename)) {
            return '';
        }

        return format_size($attachments->size((string) $this->filename));
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
