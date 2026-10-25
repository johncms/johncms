<?php

namespace Johncms\Modules\News\Domain\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Johncms\Casts\FormattedDate;
use Johncms\Modules\News\Application\Section;

/**
 * @mixin Builder
 *
 * @property int $id
 * @property int|null $parent 0 or null at the root
 * @property string $name
 * @property string $code
 * @property string|null $text
 * @property string|null $keywords
 * @property string|null $description
 * @property string|null $created_at Formatted for display
 * @property string|null $updated_at Formatted for display
 *
 * @property NewsSection|null $parentSection
 * @property \Illuminate\Database\Eloquent\Collection<int, NewsSection> $childSections
 * @property string $url
 */
class NewsSection extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'parent',
        'name',
        'code',
        'text',
        'keywords',
        'description',
    ];

    protected $casts = [
        'parent'      => 'integer',
        'created_at'  => FormattedDate::class,
        'updated_at'  => FormattedDate::class,
    ];

    protected $appends = [
        'url',
    ];

    public function parentSection(): HasOne
    {
        return $this->hasOne(__CLASS__, 'id', 'parent');
    }

    public function childSections(): HasMany
    {
        return $this->hasMany(__CLASS__, 'parent', 'id');
    }

    /**
     * Returns the url of the section page
     *
     * @return string
     */
    public function getUrlAttribute(): string
    {
        $url = '';
        if (! empty($this->parent)) {
            $url = (new Section())->getCachedPath($this->parent) . '/';
        }
        return '/news/' . $url . $this->code . '/';
    }
}
