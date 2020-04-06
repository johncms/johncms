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
use Illuminate\Database\Eloquent\Relations\HasOne;
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
 * @property string $curators
 * @property bool $pinned
 * @property bool $has_poll
 * @property int $old_id - Устаревшее.
 *
 * @property ForumSection $section
 */
class ForumTopic extends Model
{
    /**
     * Название таблицы
     *
     * @var string
     */
    protected $table = 'forum_topic';

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
     * Связь с родительским разделом
     *
     * @return HasOne
     */
    public function section(): HasOne
    {
        return $this->hasOne(ForumSection::class, 'id', 'section_id');
    }
}
