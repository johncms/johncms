<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Johncms\Media\MediaEmbed;
use Johncms\Security\HTMLPurifier;
use Johncms\Smilies\SmiliesRendererInterface;
use Simba77\EmbedMedia\Embed;

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

    protected \HTMLPurifier $purifier;
    protected Embed $media;
    protected SmiliesRendererInterface $smiliesRenderer;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->purifier = di(HTMLPurifier::class);
        $this->media = di(MediaEmbed::class);
        $this->smiliesRenderer = di(SmiliesRendererInterface::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(DownloadCategory::class, 'refid');
    }

    public function getAboutHtmlAttribute(): string
    {
        $text = $this->purifier->purify((string) $this->about);
        $text = $this->media->embedMedia($text);
        return $this->smiliesRenderer->render($text);
    }
}
