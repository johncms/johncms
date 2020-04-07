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
use Illuminate\Database\Eloquent\Relations\HasOne;
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
 */
class ForumMessage extends Model
{
    /**
     * Название таблицы
     *
     * @var string
     */
    protected $table = 'forum_messages';

    public $timestamps = false;

    protected $casts = [
        'pinned'  => 'bool',
        'deleted' => 'bool',
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
     * Связь с темой форума к которой относится данный пост
     *
     * @return HasOne
     */
    public function topic(): HasOne
    {
        return $this->hasOne(ForumTopic::class, 'id', 'topic_id');
    }
}
