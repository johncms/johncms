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
     * The website of the user: a link when the address is one a browser may follow, the address
     * as plain text otherwise. Null when the user has given none.
     *
     * The field holds an address, not markup, so it is not run through the HTML sanitizer: an
     * allow list of schemes is what keeps `javascript:` out of an href.
     */
    public function getWebsiteAttribute(): ?Markup
    {
        // The address is stored escaped by the profile form, and escaped again below on output.
        $address = trim(html_entity_decode((string) $this->www, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($address === '') {
            return null;
        }

        $escaped = htmlspecialchars($address, ENT_QUOTES, 'UTF-8');
        if (! $this->isFollowableAddress($address)) {
            return new Markup($escaped, 'UTF-8');
        }

        // nofollow: the address is whatever a visitor typed about themselves.
        return new Markup('<a href="' . $escaped . '" rel="nofollow noopener">' . $escaped . '</a>', 'UTF-8');
    }

    /**
     * Whether the address is an http(s) URL. parse_url is asked for the scheme rather than
     * filter_var for a verdict, because the scheme is the whole question here.
     */
    private function isFollowableAddress(string $address): bool
    {
        $scheme = parse_url($address, PHP_URL_SCHEME);
        if (! is_string($scheme) || ! in_array(strtolower($scheme), ['http', 'https'], true)) {
            return false;
        }

        return filter_var($address, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Фотография польхователя
     *
     * @return array
     */
    public function getPhotoAttribute(): array
    {
        $images = di(UserImages::class);
        if (! $images->hasPhoto($this->id)) {
            return [];
        }

        return [
            'photo'         => $images->photoUrl($this->id),
            'photo_preview' => $images->photoPreviewUrl($this->id),
        ];
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
