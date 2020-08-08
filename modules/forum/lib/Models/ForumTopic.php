<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Forum\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Johncms\Casts\Serialize;
use Johncms\System\Legacy\Tools;
use Johncms\Users\User;

/**
 * Class Topic
 *
 * @package Forum\Models
 *
 * @mixin Builder
 * @property int $id
 * @property int $section_id
 * @property string $name
 * @property string $description
 * @property string $meta_description
 * @property string $meta_keywords
 * @property int $view_count
 * @property int $user_id
 * @property string $user_name
 * @property Carbon $created_at
 * @property int $post_count
 * @property int $mod_post_count
 * @property int $last_post_date
 * @property int $last_post_author
 * @property string $last_post_author_name
 * @property int $last_message_id
 * @property int $mod_last_post_date
 * @property int $mod_last_post_author
 * @property string $mod_last_post_author_name
 * @property int $mod_last_message_id
 * @property bool $closed
 * @property string $closed_by
 * @property bool $deleted
 * @property string $deleted_by
 * @property array $curators
 * @property bool $pinned
 * @property bool $has_poll
 * @property int $old_id - Устаревшее.
 *
 * @property ForumSection $section
 * @method Builder read() - Метка прочитанного
 * @property string $url
 * @property string $show_posts_count
 * @property string $show_last_author
 * @property string $show_last_post_date
 * @property bool $has_icons
 * @property string $last_page_url
 * @property bool $unread
 * @property int $read
 * @property string $formatted_view_count
 *
 * @property int $files_count
 * @property ForumFile $files
 *
 * @property User $current_user
 * @property Tools $tools
 */
class ForumTopic extends Model
{
    use TopicMutators;

    /**
     * Название таблицы
     *
     * @var string
     */
    protected $table = 'forum_topic';

    public $timestamps = false;

    protected $casts = [
        'closed'     => 'boolean',
        'deleted'    => 'boolean',
        'pinned'     => 'boolean',
        'has_poll'   => 'boolean',
        'view_count' => 'integer',
        'curators'   => Serialize::class,
    ];

    protected $appends = [
        'url',
        'show_posts_count',
        'show_last_author',
        'show_last_post_date',
        'has_icons',
        'last_page_url',
        'unread',
    ];

    protected $fillable = [
        'section_id',
        'name',
        'description',
        'meta_description',
        'meta_keywords',
        'view_count',
        'user_id',
        'user_name',
        'created_at',
        'post_count',
        'mod_post_count',
        'last_post_date',
        'last_post_author',
        'last_post_author_name',
        'last_message_id',
        'mod_last_post_date',
        'mod_last_post_author',
        'mod_last_post_author_name',
        'mod_last_message_id',
        'closed',
        'closed_by',
        'deleted',
        'deleted_by',
        'curators',
        'pinned',
        'has_poll',
    ];

    /**
     * Текущий пользователь
     *
     * @var User
     */
    protected $current_user;

    /**
     * @var Tools
     */
    protected $tools;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->current_user = di(User::class);
        $this->tools = di(Tools::class);
    }

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
                /** @var User $user */
                $user = di(User::class);
                if ($user->rights < 7) {
                    $builder->where('deleted', '!=', 1)->orWhereNull('deleted');
                }
            }
        );
    }

    /**
     * Добавляем в выборку количество непрочитанных сообщений с момента последнего прочтения темы.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeRead(Builder $query): Builder
    {
        /** @var User $user */
        $user = di(User::class);
        if ($user->is_valid) {
            return $query->selectSub(
                (new ForumUnread())
                    ->selectRaw('count(*)')
                    ->whereRaw('cms_forum_rdm.time >= forum_topic.last_post_date')
                    ->whereRaw('cms_forum_rdm.topic_id = forum_topic.id')
                    ->where('user_id', '=', $user->id),
                'read'
            )
                ->addSelect('forum_topic.*');
        }
        return $query;
    }

    /**
     * Связь с родительским разделом
     *
     * @return HasOne
     */
    public function section(): HasOne
    {
        return $this->hasOne(ForumSection::class, 'id', 'section_id');
    }

    /**
     * Relation to files of the topic
     */
    public function files(): HasMany
    {
        return $this->hasMany(ForumFile::class, 'topic', 'id');
    }
}
