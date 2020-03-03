<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Notifications;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Johncms\Users\User;

/**
 * Class Notification
 *
 * @mixin Builder
 * @property int $id
 * @property string $module - Модуль, которому принадлежит уведомдение
 * @property string $event_type - Тип события
 * @property int $user_id - Идентификатор пользователя-получателя уведомления
 * @property int $sender_id - Идентификатор отправителя
 * @property array $fields - Массив полей, который будет доступен для использования в шаблонах
 */
class Notification extends Model
{
    protected $casts = [
        'fields' => 'array',
    ];

    protected $fillable = [
        'module',
        'event_type',
        'user_id',
        'sender_id',
        'fields',
    ];

    /**
     * Связь с пользователем
     *
     * @return HasOne
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    /**
     * Связь с пользователем - отправителем
     *
     * @return HasOne
     */
    public function sender(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'sender_id');
    }
}
