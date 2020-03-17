<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Users;

trait UserMutators
{
    /**
     * @var array Упрощенный массив банов для обратной совместимости
     */
    private $ban_list = [];

    /**
     * @var Ban Баны пользователя
     */
    private $active_bans = [];

    /**
     * @param string $value
     * @return string
     */
    public function getBrowserAttribute(string $value): string
    {
        return htmlspecialchars($value);
    }

    /**
     * Определяем пользователь онлайн или нет
     *
     * @return bool
     */
    public function getIsOnlineAttribute(): bool
    {
        return time() <= $this->lastdate + 300;
    }

    /**
     * Название должности пользователя
     *
     * @return string
     */
    public function getRightsNameAttribute(): string
    {
        $user_rights_names = [
            3 => d__('system', 'Forum moderator'),
            4 => d__('system', 'Download moderator'),
            5 => d__('system', 'Library moderator'),
            6 => d__('system', 'Super moderator'),
            7 => d__('system', 'Administrator'),
            9 => d__('system', 'Supervisor'),
        ];

        return array_key_exists($this->rights, $user_rights_names) ? $user_rights_names[$this->rights] : '';
    }

    /**
     * Ссылка на страницу профиля пользователя
     *
     * @return string
     */
    public function getProfileUrlAttribute(): string
    {
        return '/profile/?user=' . $this->id;
    }

    /**
     * Ссылка на страницу поиска по IP
     *
     * @return string
     */
    public function getSearchIpUrlAttribute(): string
    {
        return '/admin/search_ip/?ip=' . $this->ip;
    }

    /**
     * Ссылка на страницу поиска по IP за прокси
     *
     * @return string
     */
    public function getSearchIpViaProxyUrlAttribute(): string
    {
        return ! empty($this->ip_via_proxy) ? '/admin/search_ip/?ip=' . $this->ip_via_proxy : '';
    }

    /**
     * Проферка валидности пользователя
     *
     * @return bool
     */
    public function getIsValidAttribute(): bool
    {
        return ($this->id && $this->preg === 1);
    }

    /**
     * Получаем баны пользоваетля
     *
     * @return array
     */
    public function getBanAttribute(): array
    {
        if (! empty($this->active_bans)) {
            return $this->ban_list;
        }

        $this->ban_list = [];
        /** @var Ban $bans */
        $bans = $this->bans();
        $this->active_bans = $bans->active()->get();
        if ($this->active_bans->count()) {
            foreach ($this->active_bans as $ban) {
                /** @var Ban $ban */
                $this->ban_list[$ban->ban_type] = 1;
            }
        }

        return $this->ban_list;
    }
}
