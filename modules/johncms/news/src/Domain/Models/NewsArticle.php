<?php

namespace Johncms\Modules\News\Domain\Models;

use Carbon\Carbon;
use Johncms\Auth\CurrentUser;
use Johncms\Content\ContentRendererInterface;
use Twig\Markup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Johncms\Casts\FormattedDate;
use Johncms\Modules\News\Application\Section;
use Johncms\Users\User;

/**
 * @mixin Builder
 *
 * @property int $id
 * @property int|null $section_id 0 or null at the root
 * @property bool|null $active
 * @property string|null $active_from Formatted for display
 * @property string|null $active_to Formatted for display
 * @property string $name
 * @property string|null $page_title
 * @property string $code
 * @property string|null $preview_text
 * @property Markup $preview_text_safe
 * @property string|null $text
 * @property Markup|null $text_safe
 * @property int $view_count
 * @property string|null $keywords
 * @property string|null $description
 * @property list<string> $tags
 * @property string|null $created_at Formatted for display
 * @property string|null $updated_at Formatted for display
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property array $attached_files
 *
 * Computed properties
 * @property NewsSection|null $parentSection
 * @property User|null $author
 * @property \Illuminate\Database\Eloquent\Collection<int, NewsVote> $votes
 * @property string $url
 * @property int $rating
 * @property int $current_vote
 * @property int $comments_count
 * @property int|null $votes_sum_vote
 * @property string|null $display_date
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
        'created_at'     => FormattedDate::class,
        'updated_at'     => FormattedDate::class,
        'attached_files' => 'array',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->perPage = di(CurrentUser::class)->user()->config->kmess;
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
     * The text of the article, sanitized and with the media embedded: markup by contract, null
     * when there is nothing to show.
     */
    public function getTextSafeAttribute(): ?Markup
    {
        return $this->markup($this->text);
    }

    /**
     * The lead of the article, prepared the same way as the text. Empty markup rather than null
     * when there is none: Twig in debug mode takes an attribute of a model that is null for a
     * missing one, and asking the model for it again ends in an exception.
     */
    public function getPreviewTextSafeAttribute(): Markup
    {
        return $this->markup($this->preview_text) ?? new Markup('', 'UTF-8');
    }

    private function markup(?string $raw): ?Markup
    {
        return di(ContentRendererInterface::class)->renderOrNull((string) $raw);
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
     * A number even for an article nobody has opened yet, whose column is still null — Twig in
     * debug mode takes a null attribute of a model for a missing one.
     */
    public function getViewCountAttribute(?int $views): int
    {
        return $views ?? 0;
    }

    /**
     * The user's current vote.
     *
     * @return int
     */
    public function getCurrentVoteAttribute(): int
    {
        $user = di(CurrentUser::class);
        if (! $user->isValid()) {
            return 0;
        }

        /** @var NewsVote $vote */
        $vote = $this->votes()->where('user_id', $user->id())->first();
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
