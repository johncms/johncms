<?php

namespace News\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Johncms\Casts\DateHuman;
use Johncms\Users\User;

/**
 * @mixin Builder
 *
 * @property $id - Идентификатор
 * @property $article_id - Статья
 * @property $user_id - Идентификатор пользователя
 * @property $text - Текст с описанием
 * @property array $user_data - Некоторые данные пользователя
 * @property $created_at - Дата создания
 *
 * @property User $user
 */
class NewsComments extends Model
{
    use SoftDeletes;

    public $timestamps = false;

    protected $fillable = [
        'article_id',
        'user_id',
        'text',
        'user_data',
        'created_at',
    ];

    protected $casts = [
        'article_id' => 'integer',
        'user_id'    => 'integer',
        'user_data'  => 'array',
        'created_at' => DateHuman::class,
    ];

    protected $appends = [];

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
