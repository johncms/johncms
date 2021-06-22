<?php

namespace News\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Johncms\Casts\FormattedDate;
use Johncms\Casts\SpecialChars;
use Johncms\Media\MediaEmbed;
use Johncms\Users\User;
use News\Section;
use News\Utils\Helpers;

/**
 * @mixin Builder
 *
 * @property $id - Идентификатор
 * @property $section_id - Родительский раздел
 * @property $active - Активность
 * @property $active_from - Дата начала активности
 * @property $active_to - Дата завершения активности
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
 * @property array $attached_files
 *
 * Computed properties
 * @property NewsSection $parentSection - Родительский раздел
 * @property User $author - Author
 * @property NewsVote $votes - Votes for the article
 * @property $url - URL адрес страницы просмотра статьи
 * @property $rating - Article rating
 * @property $current_vote - The user's current vote.
 * @property $comments_count
 * @property $votes_sum_vote
 * @property $display_date
 * @method NewsArticle search()
 * @method NewsArticle active()
 * @method NewsArticle lastDays($day_count)
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class NewsArticle extends Model
{
    use SoftDeletes;

    protected $table = 'news_articles';

    protected $fillable = [
        'active',
        'active_from',
        'active_to',
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
        'attached_files',
    ];

    protected $casts = [
        'active'         => 'bool',
        'active_from'    => FormattedDate::class,
        'active_to'      => FormattedDate::class,
        'section_id'     => 'integer',
        'view_count'     => 'integer',
        'name'           => SpecialChars::class,
        'page_title'     => SpecialChars::class,
        'keywords'       => SpecialChars::class,
        'description'    => SpecialChars::class,
        'tags'           => SpecialChars::class,
        'created_at'     => FormattedDate::class,
        'updated_at'     => FormattedDate::class,
        'attached_files' => 'array',
    ];

    /** @var MediaEmbed|mixed */
    protected $media;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        /** @var User $user */
        $user = di(User::class);
        $this->perPage = $user->config->kmess;
        $this->media = di(MediaEmbed::class);
    }

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
        return $query->where('active', 1)
            ->where(
                function (Builder $builder) {
                    $builder->whereNull('active_from')->orWhere('active_from', '<=', Carbon::now());
                }
            )
            ->where(
                function (Builder $builder) {
                    $builder->whereNull('active_to')->orWhere('active_to', '>=', Carbon::now());
                }
            );
    }

    /**
     * For last n days
     *
     * @param Builder $query
     * @param int $days
     * @return Builder
     */
    public function scopeLastDays(Builder $query, int $days = 3): Builder
    {
        $date = Carbon::now()->addDays(-$days)->format('Y-m-d 00:00:00');
        return $query->where('active_from', '>=', $date)->orWhere(
            function (Builder $builder) use ($date) {
                $builder->whereNull('active_from')->where('created_at', '>=', $date);
            }
        );
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
            $url = (new Section())->getCachedPath($this->section_id) . '/';
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
        $text = Helpers::purifyHtml($this->text);
        return $this->media->embedMedia($text);
    }

    /**
     * Returns the url of the section page
     *
     * @return string
     */
    public function getPreviewTextSafeAttribute(): string
    {
        $text = Helpers::purifyHtml($this->preview_text);
        return $this->media->embedMedia($text);
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
     * @psalm-suppress UndefinedThisPropertyFetch
     * @return int
     */
    public function getRatingAttribute(): int
    {
        return (int) $this->votes_sum_vote;
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

    public function getDisplayDateAttribute(): string
    {
        if (! empty($this->active_from)) {
            return $this->active_from;
        }
        return $this->created_at;
    }
}
