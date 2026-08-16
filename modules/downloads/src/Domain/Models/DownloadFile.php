<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Johncms\Media\MediaEmbed;
use Johncms\Security\HtmlSanitizerInterface;
use Johncms\Smilies\SmiliesRendererInterface;
use Simba77\EmbedMedia\Embed;
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

    protected HtmlSanitizerInterface $sanitizer;
    protected Embed $media;
    protected SmiliesRendererInterface $smiliesRenderer;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->sanitizer = di(HtmlSanitizerInterface::class);
        $this->media = di(MediaEmbed::class);
        $this->smiliesRenderer = di(SmiliesRendererInterface::class);
    }

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
        $text = $this->sanitizer->sanitize((string) $this->about);
        $text = $this->smiliesRenderer->render($this->media->embedMedia($text));

        return $text === '' ? null : new Markup($text, 'UTF-8');
    }
}
