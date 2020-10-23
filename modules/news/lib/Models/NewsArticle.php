<?php

namespace News\Models;

use Johncms\Casts\FormattedDate;
use News\Utils\Helpers;
use News\Utils\SectionPathCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Johncms\Casts\SpecialChars;
use Johncms\Users\User;

/**
 * @mixin Builder
 *
 * @property $id - Идентификатор
 * @property $section_id - Родительский раздел
 * @property $active - Активность
 * @property $name - Название
 * @property $page_title - Заголовок страницы
 * @property $code - Символьный код
 * @property $preview_text - Краткое описание статьи в списке
 * @property $preview_text_safe - Краткое описание статьи в списке в безопасном виде
 * @property $text - Текст с описанием
 * @property $text_safe - Текст с описанием в безопасном виде
 * @property $view_count - Количество просмотров
 * @property $keywords - Ключевые слова
 * @property $description - Описание
 * @property $tags - Tags
 * @property $created_at - Дата создания
 * @property $updated_at - Дата изменения
 * @property $created_by - Автор
 * @property $updated_by - Пользователь, изменивший запись
 *
 * Computed properties
 * @property NewsSection $parentSection - Родительский раздел
 * @property User $author - Author
 * @property NewsVote $votes - Votes for the article
 * @property $url - URL адрес страницы просмотра статьи
 * @property $meta_title
 * @property $meta_keywords
 * @property $meta_description
 * @property $rating - Article rating
 * @property $current_vote - The user's current vote.
 * @property $comments_count
 * @method NewsArticle search()
 * @method NewsArticle active()
 */
class NewsArticle extends Model
{
    use SoftDeletes;

    protected $table = 'news_articles';

    protected $fillable = [
        'active',
        'section_id',
        'name',
        'page_title',
        'code',
        'keywords',
        'description',
        'preview_text',
        'text',
        'tags',
        'view_count',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'active'      => 'bool',
        'section_id'  => 'integer',
        'view_count'  => 'integer',
        'name'        => SpecialChars::class,
        'page_title'  => SpecialChars::class,
        'keywords'    => SpecialChars::class,
        'description' => SpecialChars::class,
        'tags'        => SpecialChars::class,
        'created_at'  => FormattedDate::class,
        'updated_at'  => FormattedDate::class,
    ];

    private $rating_cache;

    /**
     * Adding a search index to the query
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeSearch(Builder $query): Builder
    {
        return $query->leftJoin('news_search_index', 'news_articles.id', '=', 'news_search_index.article_id')
            ->addSelect('news_articles.*');
    }

    /**
     * Only active
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', 1);
    }

    public function parentSection(): HasOne
    {
        return $this->hasOne(NewsSection::class, 'id', 'section_id');
    }

    public function author(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    /**
     * Returns the url of the section page
     *
     * @return string
     */
    public function getUrlAttribute(): string
    {
        $url = '';
        if (! empty($this->section_id)) {
            /** @var SectionPathCache $cache */
            $cache = di(SectionPathCache::class);
            $url = $cache->getSectionPath($this->section_id) . '/';
        }
        return '/news/' . $url . $this->code . '.html';
    }

    /**
     * Returns the url of the section page
     *
     * @return string
     */
    public function getTextSafeAttribute(): string
    {
        return Helpers::purifyHtml($this->text);
    }

    /**
     * Returns the url of the section page
     *
     * @return string
     */
    public function getPreviewTextSafeAttribute(): string
    {
        return Helpers::purifyHtml($this->preview_text);
    }

    /**
     * Meta title
     *
     * @return string|string[]
     */
    public function getMetaTitleAttribute()
    {
        if (! empty($this->page_title)) {
            return $this->page_title;
        }
        $config = di('config')['news'];
        return ! empty($config['article_title']) ? str_replace('#article_name#', $this->name, $config['article_title']) : $this->name;
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
        return str_replace('#article_name#', $this->name, $config['article_meta_keywords']);
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
        return str_replace('#article_name#', $this->name, $config['article_meta_description']);
    }

    /**
     * Votes
     *
     * @return HasMany
     */
    public function votes(): HasMany
    {
        return $this->hasMany(NewsVote::class, 'article_id', 'id');
    }

    /**
     * Comments
     *
     * @return HasMany
     */
    public function comments(): HasMany
    {
        return $this->hasMany(NewsComments::class, 'article_id', 'id');
    }

    /**
     * Article rating
     *
     * @return int
     */
    public function getRatingAttribute(): int
    {
        if ($this->rating_cache !== null) {
            return $this->rating_cache;
        }
        $this->rating_cache = $this->votes()->sum('vote');
        return $this->rating_cache;
    }

    /**
     * The user's current vote.
     *
     * @return int
     */
    public function getCurrentVoteAttribute(): int
    {
        /** @var User $user */
        $user = di(User::class);
        if (! $user->isValid()) {
            return 0;
        }

        /** @var NewsVote $vote */
        $vote = $this->votes()->where('user_id', $user->id)->first();
        if ($vote === null) {
            return 0;
        }

        return $vote->vote;
    }

    /**
     * Tags
     *
     * @param $value
     * @return array
     */
    public function getTagsAttribute($value): array
    {
        $tags = [];
        if (! empty($value)) {
            $tags = explode(',', $value);
            $tags = array_map('trim', $tags);
            $tags = array_map('htmlspecialchars', $tags);
        }
        return $tags;
    }
}
