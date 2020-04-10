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

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Johncms\Casts\Ip;
use Johncms\Casts\TimeToDate;
use Johncms\System\Legacy\Tools;
use Johncms\Users\User;

/**
 * Class Message
 *
 * @package Forum\Models
 *
 * @mixin Builder
 * @property int $id
 * @property int $topic_id
 * @property string $text
 * @property int $date
 * @property int $user_id
 * @property string $user_name
 * @property string $user_agent
 * @property int $ip
 * @property int $ip_via_proxy
 * @property bool $pinned
 * @property string $editor_name
 * @property int $edit_time
 * @property int $edit_count
 * @property bool $deleted
 * @property string $deleted_by
 * @property int $old_id - Удалить
 *
 * @property ForumTopic $topic
 * @property User $user
 * @property User $user_data
 * @property ForumFile $files
 * @property string $url
 * @property string $edit_url
 * @property string $delete_url
 * @property string $restore_url
 * @property string $post_time
 * @property string $post_text
 * @property string $post_preview
 * @property string $search_ip_url
 * @property string $search_ip_via_proxy_url
 * @property string $user_profile_link
 *
 * @property string $rights
 *
 * @method ForumMessage users()
 *
 */
class ForumMessage extends Model
{
    use MessageMutators;

    /**
     * Table name
     *
     * @var string
     */
    protected $table = 'forum_messages';

    public $timestamps = false;

    public $can_edit = false;
    public $reply_url = '';
    public $quote_url = '';

    protected $casts = [
        'pinned'       => 'bool',
        'deleted'      => 'bool',
        'ip'           => Ip::class,
        'ip_via_proxy' => Ip::class,
        'edit_time'    => TimeToDate::class,
    ];

    protected $fillable = [
        'topic_id',
        'text',
        'date',
        'user_id',
        'user_name',
        'user_agent',
        'ip',
        'ip_via_proxy',
        'pinned',
        'editor_name',
        'edit_time',
        'edit_count',
        'deleted',
        'deleted_by',
    ];

    protected $appends = [
        'url',
        'post_time',
        'post_text',
        'edit_time',
        'edit_url',
        'delete_url',
        'restore_url',
    ];

    /**
     * Current user
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
     * Global scopes
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
     * Adding user data to the query
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeUsers(Builder $query): Builder
    {
        return $query->leftJoin('users', 'forum_messages.user_id', '=', 'users.id')
            ->addSelect(
                [
                    'forum_messages.*',
                    'users.rights',
                    'users.lastdate',
                    'users.status',
                    'users.datereg',
                ]
            );
    }

    /**
     * Relationship to the parent topic
     *
     * @return HasOne
     */
    public function topic(): HasOne
    {
        return $this->hasOne(ForumTopic::class, 'id', 'topic_id');
    }

    /**
     * Relationship to the author of the post.
     *
     * @return HasOne
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    /**
     * Relationship to attached files.
     *
     * @return HasMany
     */
    public function files(): HasMany
    {
        return $this->hasMany(ForumFile::class, 'post', 'id');
    }
}
