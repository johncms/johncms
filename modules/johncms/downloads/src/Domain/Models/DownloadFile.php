<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Johncms\Content\ContentRendererInterface;
use Twig\Markup;

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
