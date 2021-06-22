<?php

namespace News\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Builder
 *
 * @property $id - Идентификатор
 * @property $article_id - Идентификатор статьи
 * @property $user_id - Идентификатор пользователя
 * @property $vote - Голос
 */
class NewsVote extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'article_id',
        'user_id',
        'vote',
    ];

    protected $casts = [
        'article_id' => 'integer',
        'user_id'    => 'integer',
        'vote'       => 'integer',
    ];
}
