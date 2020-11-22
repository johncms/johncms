<?php

namespace News\Models;

use Johncms\Casts\FormattedDate;
use News\Utils\SectionPathCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Johncms\Casts\SpecialChars;

/**
 * @mixin Builder
 *
 * @property $id - Идентификатор
 * @property $parent - Родительский раздел
 * @property $name - Название раздела
 * @property $code - Символьный код раздела
 * @property $text - Текст с описанием
 * @property $keywords - Ключевые слова
 * @property $description - Описание
 * @property $created_at - Дата создания
 * @property $updated_at - Дата изменения
 *
 * @property NewsSection $parentSection - Родительский раздел
 * @property NewsSection $childSections - Дочерние раздел
 * @property $url - Ссылка на страницу просмотра раздела
 * @property $meta_title
 * @property $meta_keywords
 * @property $meta_description
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
        'name'        => SpecialChars::class,
        'keywords'    => SpecialChars::class,
        'description' => SpecialChars::class,
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
            /** @var SectionPathCache $cache */
            $cache = di(SectionPathCache::class);
            $url = $cache->getSectionPath($this->parent) . '/';
        }
        return '/news/' . $url . $this->code . '/';
    }

    /**
     * Meta title
     *
     * @return string|string[]
     */
    public function getMetaTitleAttribute()
    {
        $config = di('config')['news'];
        return ! empty($config['section_title']) ? str_replace('#section_name#', $this->name, $config['section_title']) : $this->name;
    }

    /**
     * Meta keywords
     *
     * @return string|string[]
     */
    public function getMetaKeywordsAttribute()
    {
        if (! empty($this->keywords)) {
            return $this->keywords;
        }
        $config = di('config')['news'];
        return str_replace('#section_name#', $this->name, $config['section_meta_keywords']);
    }

    /**
     * Meta description
     *
     * @return string|string[]
     */
    public function getMetaDescriptionAttribute()
    {
        if (! empty($this->description)) {
            return $this->description;
        }
        $config = di('config')['news'];
        return str_replace('#section_name#', $this->name, $config['section_meta_description']);
    }
}
