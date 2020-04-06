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

/**
 * Class ForumUnread
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
 *
 * @property ForumTopic $topic
 */
class ForumUnread extends Model
{
    /**
     * Название таблицы
     *
     * @var string
     */
    protected $table = 'cms_forum_rdm';

    public $timestamps = false;

    protected $fillable = [
        'topic_id',
        'user_id',
        'time',
    ];
}
