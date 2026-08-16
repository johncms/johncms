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
use Johncms\Auth\Authorization\StaffTitles;
use Johncms\Security\HtmlSanitizerInterface;
use Johncms\Smilies\SmiliesRendererInterface;
use Johncms\System\i18n\Translator;
use Johncms\System\Users\UserConfig;
use Twig\Markup;

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
     * User agent, as the client sent it. It is escaped where it is printed, like any other
     * value that came from outside.
     */
    public function getBrowserAttribute(string $value): string
    {
        return $value;
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
     * The caption under the nickname: the highest role granted to the account, empty for an
     * account holding none.
     */
    public function getRightsNameAttribute(): string
    {
        return di(StaffTitles::class)->titleFor((int) $this->id);
    }

    /**
     * Ссылка на страницу профиля пользователя
     *
     * @return string
     */
    public function getProfileUrlAttribute(): string
    {
        return '/profile/' . $this->id;
    }

    /**
     * Ссылка на страницу поиска по IP
     *
     * @return string
     */
    public function getSearchIpUrlAttribute(): string
    {
        return '/admin/ip-search?ip=' . $this->ip;
    }

    /**
     * Ссылка на страницу whois IP
     *
     * @return string
     */
    public function getWhoisIpUrlAttribute(): string
    {
        return '/admin/ip-whois?ip=' . $this->ip;
    }

    /**
     * Ссылка на страницу поиска по IP за прокси
     *
     * @return string
     */
    public function getSearchIpViaProxyUrlAttribute(): string
    {
        return ! empty($this->ip_via_proxy) ? '/admin/ip-search?ip=' . $this->ip_via_proxy : '';
    }

    /**
     * Ссылка на страницу whois IP за прокси
     *
     * @return string
     */
    public function getWhoisIpViaProxyUrlAttribute(): string
    {
        return ! empty($this->ip_via_proxy) ? '/admin/ip-whois?ip=' . $this->ip_via_proxy : '';
    }

    /**
     * Проферка валидности пользователя
     *
     * @return bool
     */
    public function getIsValidAttribute(): bool
    {
        $isEmailConfirmationEnabled = config('johncms.user_email_confirmation');
        return ($this->id && $this->preg && (empty($isEmailConfirmationEnabled) || $this->email_confirmed));
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
     * Where the user is on the site, as a link: markup by contract, like the formatter that
     * builds it.
     */
    public function getDisplayPlaceAttribute(): Markup
    {
        return di(UserPlaceFormatterInterface::class)->format($this->place);
    }

    /**
     * The "about" field, sanitized and with the smilies rendered: markup by contract, null when
     * the user has written nothing.
     */
    public function getFormattedAboutAttribute(): ?Markup
    {
        $sanitizer = di(HtmlSanitizerInterface::class);
        $about = di(SmiliesRendererInterface::class)->render($sanitizer->sanitize((string) $this->about));

        return $about === '' ? null : new Markup($about, 'UTF-8');
    }

    /**
     * The website of the user, sanitized the same way.
     */
    public function getWebsiteAttribute(): ?Markup
    {
        $sanitizer = di(HtmlSanitizerInterface::class);
        $website = $sanitizer->sanitize((string) $this->www);

        return $website === '' ? null : new Markup($website, 'UTF-8');
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
