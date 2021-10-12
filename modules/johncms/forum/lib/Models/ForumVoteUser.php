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
 * Class ForumVoteUser
 *
 * @package Forum\Models
 *
 * @mixin Builder
 * @property int $id
 * @property int $user
 * @property int $topic
 * @property int $vote
 */
class ForumVoteUser extends Model
{
    protected $table = 'cms_forum_vote_users';

    public $timestamps = false;

    protected $fillable = [
        'user',
        'topic',
        'vote',
    ];
}
