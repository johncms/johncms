<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Johncms\Content\ContentRendererInterface;
use Twig\Markup;

/**
 * @property int         $id
 * @property int         $refid      The category, 0 for the main page of the downloads.
 * @property string      $dir        Folder on disk.
 * @property int         $time
 * @property string      $name       Name of the file on disk.
 * @property string|null $slug
 * @property string      $text       Text of the download link.
 * @property string      $rus_name   Title shown on the site.
 * @property int         $type       2 a published file, 3 one waiting for a moderator.
 * @property int         $user_id
 * @property int         $field      How many times it was downloaded.
 * @property string      $rate       Votes, "plus|minus".
 * @property string      $about
 * @property string      $desc
 * @property int         $comm_count
 */
final class DownloadFile extends Model
{
    protected $table = 'download__files';
    public $timestamps = false;

    protected $fillable = [
        'refid',
        'dir',
        'time',
        'name',
        'slug',
        'text',
        'rus_name',
        'type',
        'user_id',
        'about',
        'desc',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(DownloadCategory::class, 'refid');
    }

    /**
     * The description of the file, sanitized and with the media and the smilies rendered: markup
     * by contract, null when there is nothing to show.
     */
    public function getAboutHtmlAttribute(): ?Markup
    {
        // Resolved here rather than in the constructor: a listing hydrates a hundred rows and
        // only the ones actually shown ask for their description.
        return di(ContentRendererInterface::class)->renderOrNull((string) $this->about);
    }
}
