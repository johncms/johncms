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
use Johncms\Casts\DateHuman;
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
 * @property int $entity_id - Идентификатор сущности к которой относится уведомление (например сообщение на форуме)
 * @property array $fields - Массив полей, который будет доступен для использования в шаблонах
 *
 * @property array $message - Вычисляемое свойство - сообщение
 * @property array $read_at - Дата прочтения
 * @property array $created_at - Дата создания
 *
 * @method Builder unread() - Предустановленное условие для выборки непрочитанных
 */
class Notification extends Model
{
    protected $notification_templates = [];

    protected $casts = [
        'fields'     => 'array',
        'created_at' => DateHuman::class,
        'updated_at' => DateHuman::class,
        'read_at'    => DateHuman::class,
    ];

    protected $fillable = [
        'module',
        'event_type',
        'user_id',
        'sender_id',
        'entity_id',
        'fields',
        'read_at',
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
                /** @var \Johncms\System\Users\User $user */
                $user = di(\Johncms\System\Users\User::class);
                $builder->where('user_id', '=', $user->id);
            }
        );
    }

    /**
     * Выборка только непрочитанных
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

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

    /**
     * Шаблоны сообщений
     *
     * @return array|mixed
     */
    private function getTemplates()
    {
        if (! empty($this->notification_templates)) {
            return $this->notification_templates;
        }

        $local = [];
        $global = require CONFIG_PATH . 'notifications.global.php';
        if (file_exists(CONFIG_PATH . 'notifications.local.php')) {
            $local = require CONFIG_PATH . 'notifications.local.php';
        }

        $this->notification_templates = array_replace_recursive($global, $local);
        return $this->notification_templates;
    }

    /**
     * Обработанное сообщение
     */
    public function getMessageAttribute()
    {
        $this->getTemplates();
        $message = 'Template is not defined';
        if (
            isset($this->notification_templates[$this->module]['events']) &&
            array_key_exists($this->event_type, $this->notification_templates[$this->module]['events'])
        ) {
            $message = $this->notification_templates[$this->module]['events'][$this->event_type]['message'];
            if (is_callable($message)) {
                $message = $message($this->fields);
            } else {
                foreach ($this->fields as $key => $value) {
                    $message = str_replace('#' . $key . '#', $value, $message);
                }
            }
        }
        return $message;
    }
}
