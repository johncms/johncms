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

trait GuestSessionMutators
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
