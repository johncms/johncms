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

use Carbon\Carbon;
use Johncms\System\i18n\Translator;
use Johncms\System\Legacy\Tools;
use Johncms\System\Users\UserConfig;

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
     * User agent
     *
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
     * Ссылка на страницу whois IP
     *
     * @return string
     */
    public function getWhoisIpUrlAttribute(): string
    {
        return '/admin/ip_whois/?ip=' . $this->ip;
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
     * Ссылка на страницу whois IP за прокси
     *
     * @return string
     */
    public function getWhoisIpViaProxyUrlAttribute(): string
    {
        return ! empty($this->ip_via_proxy) ? '/admin/ip_whois/?ip=' . $this->ip_via_proxy : '';
    }

    /**
     * Проферка валидности пользователя
     *
     * @return bool
     */
    public function getIsValidAttribute(): bool
    {
        $config = di('config')['johncms'];
        return ($this->id && $this->preg && (empty($config['user_email_confirmation']) || $this->email_confirmed));
    }

    /**
     * Получаем время последнего визита
     *
     * @return string
     */
    public function getLastVisitAttribute(): string
    {
        /** @var Translator $translator */
        $translator = di(Translator::class);
        return $this->is_online ? '' : Carbon::createFromTimestampUTC($this->lastdate)
            ->locale($translator->getLocale())
            ->diffForHumans(['join' => false, 'parts' => 2]);
    }

    /**
     * У пользователя день рождения?
     *
     * @return bool
     */
    public function getIsBirthdayAttribute(): bool
    {
        return ($this->dayb === date('j') && $this->monthb === date('n'));
    }

    /**
     * День рождения пользователя
     *
     * @return string
     */
    public function getBirthdayDateAttribute(): string
    {
        return (empty($this->dayb) ? '' : sprintf('%02d', $this->dayb) . '.' . sprintf('%02d', $this->monthb) . '.' . $this->yearofbirth);
    }

    /**
     * Местоположение пользователя
     *
     * @return string
     */
    public function getDisplayPlaceAttribute(): string
    {
        /** @var Tools $tools */
        $tools = di(Tools::class);
        return $tools->displayPlace($this->place);
    }

    /**
     * Обработанное поле О себе.
     *
     * @return string
     */
    public function getFormattedAboutAttribute(): string
    {
        /** @var Tools $tools */
        $tools = di(Tools::class);
        return $tools->smilies($tools->checkout($this->about, 1, 1));
    }

    /**
     * Обработанное поле сайт
     *
     * @return string
     */
    public function getWebsiteAttribute(): string
    {
        /** @var Tools $tools */
        $tools = di(Tools::class);
        return $tools->checkout($this->www, 0, 1);
    }

    /**
     * Фотография польхователя
     *
     * @return array
     */
    public function getPhotoAttribute(): array
    {
        $photo = [];
        if (file_exists(UPLOAD_PATH . 'users/photo/' . $this->id . '_small.jpg')) {
            $photo['photo'] = '/upload/users/photo/' . $this->id . '.jpg';
            $photo['photo_preview'] = '/upload/users/photo/' . $this->id . '_small.jpg';
        }
        return $photo;
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

    /**
     * User settings
     *
     * @return UserConfig
     */
    public function getConfigAttribute(): UserConfig
    {
        return $this->set_user;
    }
}
