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
     * @param int $value
     * @return string
     */
    public function getIpAttribute(int $value): string
    {
        return long2ip($value);
    }

    /**
     * @param string $value
     */
    public function setIpAttribute(string $value): void
    {
        $this->attributes['ip'] = ip2long($value);
    }

    /**
     * @param int $value
     * @return string
     */
    public function getIpViaProxyAttribute(int $value): string
    {
        return ! empty($value) ? long2ip($value) : '';
    }

    /**
     * @param string $value
     */
    public function setIpViaProxyAttribute(string $value): void
    {
        $this->attributes['ip_via_proxy'] = ip2long($value);
    }

    /**
     * @param string $value
     * @return array
     */
    public function getSetUserAttribute(string $value): array
    {
        return ! empty($value) ? unserialize($value, ['allowed_classes' => false]) : [];
    }

    /**
     * @param string $value
     */
    public function setSetUserAttribute(string $value): void
    {
        $this->attributes['set_user'] = serialize($value);
    }

    /**
     * @param string $value
     * @return array
     */
    public function getSetForumAttribute(string $value): array
    {
        return ! empty($value) ? unserialize($value, ['allowed_classes' => false]) : [];
    }

    /**
     * @param string $value
     */
    public function setSetForumAttribute(string $value): void
    {
        $this->attributes['set_forum'] = serialize($value);
    }

    /**
     * @param string $value
     * @return array
     */
    public function getSetMailAttribute(string $value): array
    {
        return ! empty($value) ? unserialize($value, ['allowed_classes' => false]) : [];
    }

    /**
     * @param string $value
     */
    public function setSetMailAttribute(string $value): void
    {
        $this->attributes['set_mail'] = serialize($value);
    }

    /**
     * @param string $value
     * @return array
     */
    public function getSmileysAttribute(string $value): array
    {
        return ! empty($value) ? unserialize($value, ['allowed_classes' => false]) : [];
    }

    /**
     * @param string $value
     */
    public function setSmileysAttribute(string $value): void
    {
        $this->attributes['smileys'] = serialize($value);
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
}
