<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Modules\Album\Application\Services\AlbumPermissions;
use Johncms\Modules\Downloads\Application\Services\DownloadsPermissions;
use Johncms\Modules\Forum\Application\Services\ForumPermissions;
use Johncms\Modules\Guestbook\Application\Services\GuestbookPermissions;
use Johncms\Modules\Library\Application\Services\LibraryPermissions;
use Johncms\Notifications\Notification;
use Johncms\Auth\CurrentUser;
use Johncms\Cache\CacheInterface;
use PDO;
use Symfony\Component\HttpFoundation\RequestStack;

class Counters
{
    /** Default counters cache lifetime, in seconds */
    private const CACHE_TTL = 600;

    /** Library counters cache lifetime, in seconds */
    private const LIBRARY_CACHE_TTL = 3200;

    /** Online counters cache lifetime, in seconds */
    private const ONLINE_CACHE_TTL = 10;

    /** Tag every counter is filed under, so that all of them can be invalidated at once */
    private const CACHE_TAG = 'counters';

    /** @var PDO */
    private $db;

    /** @var string */
    private $homeurl;

    /** @var CurrentUser */
    private $user;

    /** @var CacheInterface */
    private $cache;

    /** @var RequestStack */
    private $requestStack;

    private AccessCheckerInterface $accessChecker;

    public function __construct(
        PDO $pdo,
        CurrentUser $user,
        string $homeUrl,
        CacheInterface $cache,
        RequestStack $requestStack,
        AccessCheckerInterface $accessChecker
    ) {
        $this->db = $pdo;
        $this->user = $user;
        $this->homeurl = $homeUrl;
        $this->cache = $cache;
        $this->requestStack = $requestStack;
        $this->accessChecker = $accessChecker;
    }

    /**
     * Whether the request being served is the home page. Set by HomepageController on the request
     * itself: it used to be the _IS_HOMEPAGE constant, which belongs to the process — once the
     * home page had been served, every later page of the same worker looked like the home page.
     */
    private function isHomePage(): bool
    {
        return (bool) $this->requestStack->getCurrentRequest()?->attributes->get('is_homepage', false);
    }

    /**
     * Счетчик Фотоальбомов пользователей
     *
     * @return string
     * @deprecated use albumCounters
     * TODO: содержимое albumCounters перенести в этот метод после проверки на использование
     */
    public function album()
    {
        $counters = $this->albumRawCounters();

        $newcount = 0;
        if ($counters['new_adm'] && $this->accessChecker->allows(AlbumPermissions::MODERATE)) {
            $newcount = $counters['new_adm'];
        } elseif ($counters['new']) {
            $newcount = $counters['new'];
        }

        return $counters['album'] . '&#160;/&#160;' . $counters['photo'] .
            ($newcount ? '&#160;/&#160;<span class="red"><a href="' . $this->homeurl . '/album/top">+' . $newcount . '</a></span>' : '');
    }

    /**
     * Cached raw album counters
     *
     * @return array{album: int, photo: int, new: int, new_adm: int}
     */
    private function albumRawCounters(): array
    {
        return $this->cache->remember('counters_albums', self::CACHE_TTL, function (): array {
            $recent = time() - 259200;

            return [
                'album'   => (int) $this->db->query('SELECT COUNT(DISTINCT `user_id`) FROM `cms_album_files`')->fetchColumn(),
                'photo'   => (int) $this->db->query('SELECT COUNT(*) FROM `cms_album_files`')->fetchColumn(),
                'new'     => (int) $this->db->query('SELECT COUNT(*) FROM `cms_album_files` WHERE `time` > ' . $recent . ' AND `access` = 4')->fetchColumn(),
                'new_adm' => (int) $this->db->query('SELECT COUNT(*) FROM `cms_album_files` WHERE `time` > ' . $recent . ' AND `access` > 1')->fetchColumn(),
            ];
        }, tags: [self::CACHE_TAG]);
    }

    /**
     * Счетчик загруз центра
     *
     * @return string
     * @deprecated use downloadsCounters
     * TODO: содержимое downloadsCounters перенести в этот метод после проверки на использование
     */
    public function downloads()
    {
        $counters = $this->downloadsRawCounters();

        $total = $counters['total'];
        $new = $counters['new'];
        $mod = $counters['mod'];

        if ($new > 0) {
            $total .= '&nbsp;/&nbsp;<span class="red"><a href="downloads/?act=new_files">+' . $new . '</a></span>';
        }

        if ($this->accessChecker->allows(DownloadsPermissions::MODERATE)) {
            if ($mod) {
                $total .= '&nbsp;/&nbsp;<span class="red"><a href="/downloads/moderation">м. ' . $mod . '</a></span>';
            }
        }

        return $total;
    }

    /**
     * Статистика Форума
     *
     * @return string
     * @deprecated use forumCounters
     * TODO: содержимое forumCounters перенести в этот метод после проверки на использование
     */
    public function forum()
    {
        $new = '';
        $counters = $this->forumRawCounters();

        if ($this->user->isValid() && ($new_msg = $this->forumNew()) > 0) {
            $new = '&#160;/&#160;<span class="red"><a href="' . $this->homeurl . '/forum/unread/">+' . $new_msg . '</a></span>';
        }

        return $counters['topics'] . '&#160;/&#160;' . $counters['messages'] . $new;
    }

    /**
     * Счетчик непрочитанных тем на форуме
     *
     * $mod = 0   Возвращает число непрочитанных тем
     * $mod = 1   Выводит ссылки на непрочитанное
     *
     * @param int $mod
     * @return bool|int|string
     * @deprecated use forumUnreadCount
     */
    public function forumNew($mod = 0)
    {
        if ($this->user->isValid()) {
            $total = $this->db->query(
                "SELECT COUNT(*) FROM `forum_topic`
                LEFT JOIN `cms_forum_rdm` ON `forum_topic`.`id` = `cms_forum_rdm`.`topic_id` AND `cms_forum_rdm`.`user_id` = '" . $this->user->id() . "'
                WHERE (`cms_forum_rdm`.`topic_id` IS NULL OR `forum_topic`.`last_post_date` > `cms_forum_rdm`.`time`)
                " . ($this->accessChecker->allows(ForumPermissions::DELETED_VIEW) ? '' : ' AND (`forum_topic`.`deleted` != 1 OR `forum_topic`.`deleted` IS NULL)') . '
                '
            )->fetchColumn();

            if ($mod) {
                return $total ? '<a href="/forum/unread/" class="pr-2">' . d__('system', 'Unread') . '</a><span class="badge badge-pill badge-danger mr-3">' . $total . '</span>' : '';
            }

            return $total;
        }
        if ($mod) {
            return '<a href="/forum/latest-topics/">' . d__('system', 'Last activity') . '</a>';
        }

        return false;
    }

    /**
     * @return int|mixed
     */
    public function forumUnreadCount()
    {
        $total = 0;
        if ($this->user->isValid()) {
            $total = $this->db->query(
                "SELECT COUNT(*) FROM `forum_topic`
                LEFT JOIN `cms_forum_rdm` ON `forum_topic`.`id` = `cms_forum_rdm`.`topic_id` AND `cms_forum_rdm`.`user_id` = '" . $this->user->id() . "'
                WHERE (`cms_forum_rdm`.`topic_id` IS NULL OR `forum_topic`.`last_post_date` > `cms_forum_rdm`.`time`)
                " . ($this->accessChecker->allows(ForumPermissions::DELETED_VIEW) ? '' : ' AND (`forum_topic`.`deleted` != 1 OR `forum_topic`.`deleted` IS NULL)') . '
                '
            )->fetchColumn();
        }

        return $total;
    }

    /**
     * Статистика библиотеки
     *
     * @return string
     * @deprecated use libraryCounters
     * TODO: содержимое libraryCounters перенести в этот метод после проверки на использование
     */
    public function library()
    {
        $counters = $this->libraryRawCounters();

        $total = $counters['total'];
        $new = $counters['new'];
        $mod = $counters['mod'];

        if ($new) {
            $total .= '&#160;/&#160;<span class="red"><a href="' . $this->homeurl . '/library/?act=new">+' . $new . '</a></span>';
        }

        if ($mod && $this->accessChecker->allows(LibraryPermissions::MODERATE)) {
            $total .= '&#160;/&#160;<span class="red"><a href="' . $this->homeurl . '/library/premod">M:' . $mod . '</a></span>';
        }

        return $total;
    }

    /**
     * Счетчик посетителей онлайн
     *
     * @return string
     */
    public function online()
    {
        $counters = $this->cache->remember('counters_online', self::ONLINE_CACHE_TTL, function (): array {
            $online = time() - 300;

            return [
                'users'  => (int) $this->db->query('SELECT COUNT(*) FROM `users` WHERE `lastdate` > ' . $online)->fetchColumn(),
                'guests' => (int) $this->db->query('SELECT COUNT(*) FROM `cms_sessions` WHERE `lastdate` > ' . $online)->fetchColumn(),
            ];
        }, tags: [self::CACHE_TAG]);

        return $counters['users'] . ' / ' . $counters['guests'];
    }

    /**
     * Количество непрочитанных личных сообщений
     *
     * @return mixed
     */
    public function mail()
    {
        $new_mail = 0;
        if (! $this->user->isValid()) {
            $new_mail = $this->db->query(
                "SELECT COUNT(*) FROM `cms_mail`
                            LEFT JOIN `cms_contact` ON `cms_mail`.`user_id`=`cms_contact`.`from_id` AND `cms_contact`.`user_id`='" . $this->user->id() . "'
                            WHERE `cms_mail`.`from_id`='" . $this->user->id() . "'
                            AND `cms_mail`.`sys`='0'
                            AND `cms_mail`.`read`='0'
                            AND `cms_mail`.`delete`!='" . $this->user->id() . "'
                            AND `cms_contact`.`ban`!='1'"
            )->fetchColumn();
        }

        return $new_mail;
    }

    /**
     * Метод возвращает количество тем, сообщений и непрочитанных сообщений на форуме
     *
     * @return array
     */
    public function forumCounters(): array
    {
        $new_messages = 0;
        $counters = $this->forumRawCounters();

        if ($this->user->isValid() && ($new_msg = $this->forumNew()) > 0) {
            $new_messages = $new_msg;
        }

        return [
            'topics'       => $counters['topics'],
            'messages'     => $counters['messages'],
            'new_messages' => $new_messages,
        ];
    }

    /**
     * Cached raw forum counters
     *
     * @return array{topics: int, messages: int}
     */
    private function forumRawCounters(): array
    {
        return $this->cache->remember('counters_forum', self::CACHE_TTL, function (): array {
            return [
                'topics'   => (int) $this->db->query(
                    "SELECT COUNT(*)
                    FROM `forum_topic`
                    WHERE `deleted` != '1'
                    OR deleted IS NULL"
                )->fetchColumn(),
                'messages' => (int) $this->db->query(
                    "SELECT COUNT(*)
                    FROM `forum_messages`
                    WHERE `deleted` != '1'
                    OR deleted IS NULL"
                )->fetchColumn(),
            ];
        }, tags: [self::CACHE_TAG]);
    }

    /**
     * Счетчики гостевой и админклуба
     *
     * @param int $mod
     * @return array
     */
    public function guestbookCounters($mod = 0): array
    {
        $guestbook = $this->db->query('SELECT COUNT(*) FROM `guest` WHERE `adm` = 0 AND `time` > ' . (time() - 86400))->fetchColumn();
        $admin_club = 0;
        if ($this->accessChecker->allows(GuestbookPermissions::ADMIN_CLUB_VIEW)) {
            $admin_club = $this->db->query('SELECT COUNT(*) FROM `guest` WHERE `adm`=\'1\' AND `time`> ' . (time() - 86400))->fetchColumn();
        }

        return [
            'guestbook'  => $guestbook,
            'admin_club' => $admin_club,
        ];
    }

    /**
     * Счетчики загруз-центра
     *
     * @return array
     */
    public function downloadsCounters(): array
    {
        $counters = $this->downloadsRawCounters();

        return [
            'total' => $counters['total'],
            'new'   => $counters['new'],
        ];
    }

    /**
     * Cached raw downloads counters
     *
     * @return array{total: int, new: int, mod: int}
     */
    private function downloadsRawCounters(): array
    {
        return $this->cache->remember('counters_downloads', self::CACHE_TTL, function (): array {
            $old = time() - (3 * 24 * 3600);

            return [
                'total' => (int) $this->db->query("SELECT COUNT(*) FROM `download__files` WHERE `type` = '2'")->fetchColumn(),
                'new'   => (int) $this->db->query("SELECT COUNT(*) FROM `download__files` WHERE `type` = '2' AND `time` > '$old'")->fetchColumn(),
                'mod'   => (int) $this->db->query("SELECT COUNT(*) FROM `download__files` WHERE `type` = '3'")->fetchColumn(),
            ];
        }, tags: [self::CACHE_TAG]);
    }

    /**
     * Статистика библиотеки
     *
     * @return array
     */
    public function libraryCounters(): array
    {
        $counters = $this->libraryRawCounters();

        return [
            'total' => $counters['total'],
            'new'   => $counters['new'],
        ];
    }

    /**
     * Cached raw library counters
     *
     * @return array{total: int, new: int, mod: int}
     */
    private function libraryRawCounters(): array
    {
        return $this->cache->remember('counters_library', self::LIBRARY_CACHE_TTL, function (): array {
            return [
                'total' => (int) $this->db->query('SELECT COUNT(*) FROM `library_texts` WHERE `premod` = 1')->fetchColumn(),
                'new'   => (int) $this->db->query('SELECT COUNT(*) FROM `library_texts` WHERE `time` > ' . (time() - 259200) . ' AND `premod` = 1')->fetchColumn(),
                'mod'   => (int) $this->db->query('SELECT COUNT(*) FROM `library_texts` WHERE `premod` = 0')->fetchColumn(),
            ];
        }, tags: [self::CACHE_TAG]);
    }

    /**
     * Количество зарегистрированных пользователей
     *
     * @return array
     */
    public function usersCounters(): array
    {
        return $this->cache->remember('counters_users', self::CACHE_TTL, function (): array {
            return [
                'total' => (new Users\User())->approved()->count(),
                'new'   => (new Users\User())->approved()->where('datereg', '>', (time() - 86400))->count(),
            ];
        }, tags: [self::CACHE_TAG]);
    }

    /**
     * Счетчик Фотоальбомов пользователей
     *
     * @return array
     */
    public function albumCounters(): array
    {
        $counters = $this->albumRawCounters();

        $newcount = 0;
        if ($counters['new_adm'] && $this->accessChecker->allows(AlbumPermissions::MODERATE)) {
            $newcount = $counters['new_adm'];
        } elseif ($counters['new']) {
            $newcount = $counters['new'];
        }

        return [
            'album' => $counters['album'],
            'photo' => $counters['photo'],
            'new'   => $newcount,
        ];
    }

    /**
     * Счетчик всех новостей
     *
     * @deprecated
     * @return array
     */
    public function news(): array
    {
        return [
            'total' => 0,
            'new'   => 0,
        ];
    }

    /**
     * Уведомления
     *
     * @return array
     */
    public function notifications(): array
    {
        $notifications = [];

        if (! $this->user->isValid()) {
            return $notifications;
        }

        // Counted for whoever is shown it: the queue of the library belongs to the moderator of
        // the library, not to everybody who was above a 7.
        if ($this->accessChecker->allows(CorePermissions::ADMIN_ACCESS)) {
            $notifications['reg_total'] = $this->db->query("SELECT COUNT(*) FROM `users` WHERE `preg`='0'")->fetchColumn();
        }

        if ($this->accessChecker->allows(LibraryPermissions::MODERATE)) {
            $notifications['library_mod'] = $this->db->query('SELECT COUNT(*) FROM `library_texts` WHERE `premod` = 0')->fetchColumn();
        }

        if ($this->accessChecker->allows(DownloadsPermissions::MODERATE)) {
            $notifications['downloads_mod'] = $this->db->query("SELECT COUNT(*) FROM `download__files` WHERE `type` = '3'")->fetchColumn();
        }

        if (! empty($this->user->user()->ban)) {
            $notifications['ban'] = 1;
        }

        if ($this->user->user()->comm_count > $this->user->user()->comm_old) {
            $notifications['guestbook_comments'] = ($this->user->user()->comm_count - $this->user->user()->comm_old);
        }

        $notifications['new_mail'] = $this->db->query(
            "SELECT COUNT(*) FROM `cms_mail`
                            LEFT JOIN `cms_contact` ON `cms_mail`.`user_id`=`cms_contact`.`from_id` AND `cms_contact`.`user_id`='" . $this->user->id() . "'
                            WHERE `cms_mail`.`from_id`='" . $this->user->id() . "'
                            AND `cms_mail`.`sys`='0'
                            AND `cms_mail`.`read`='0'
                            AND `cms_mail`.`delete`!='" . $this->user->id() . "'
                            AND `cms_contact`.`ban`!='1'"
        )->fetchColumn();

        $notifications['new_album_comm'] = $this->db->query('SELECT COUNT(*) FROM `cms_album_files` WHERE `user_id` = \'' . $this->user->id() . '\' AND `unread_comments` = 1')->fetchColumn();

        // The column is cast to an array by the model, so there is nothing to decode here.
        $notification_settings = array_merge(
            ['show_forum_unread' => false],
            $this->user->user()->notification_settings ?? []
        );
        if ($notification_settings['show_forum_unread']) {
            $forum_counters = $this->forumCounters();
            $notifications['forum_new'] = $forum_counters['new_messages'];
        }

        $notifications['notifications'] = (new Notification())->unread()->count();
        $notifications['all'] = array_sum($notifications);

        return $notifications;
    }

    /**
     * Метод получает массив счетчиков различных систем аналитики
     *
     * @return array
     */
    public function counters(): array
    {
        $counters = [];
        $hasConsentGated = false;
        $req = $this->db->query('SELECT * FROM `cms_counters` WHERE `switch` = 1 ORDER BY `sort`');

        if ($req->rowCount()) {
            while ($res = $req->fetch()) {
                $link1 = ($res['mode'] === 1 || $res['mode'] === 2) ? $res['link1'] : $res['link2'];
                $link2 = $res['mode'] === 2 ? $res['link1'] : $res['link2'];
                $count = $this->isHomePage() ? $link1 : $link2;
                if (empty($count)) {
                    continue;
                }

                if (! empty($res['require_cookie_consent'] ?? 0)) {
                    // Defer execution until the visitor accepts the cookie banner.
                    $hasConsentGated = true;
                    $counters[] = '<template data-cookie-consent-counter>' . $count . '</template>';
                } else {
                    $counters[] = $count;
                }
            }
        }

        if ($hasConsentGated) {
            $counters[] = $this->cookieConsentActivationScript();
        }

        return $counters;
    }

    /**
     * Script that reveals consent-gated counters once the cookie banner is accepted.
     */
    private function cookieConsentActivationScript(): string
    {
        $version = (int) (config('johncms')['cookie_banner_version'] ?? 1);

        return <<<HTML
            <script>
                (function () {
                    var version = '{$version}';

                    function activate() {
                        var templates = document.querySelectorAll('template[data-cookie-consent-counter]');
                        for (var i = 0; i < templates.length; i++) {
                            var tpl = templates[i];
                            var container = document.createElement('div');
                            if (tpl.content) {
                                container.appendChild(tpl.content.cloneNode(true));
                            } else {
                                container.innerHTML = tpl.innerHTML;
                            }
                            var scripts = container.querySelectorAll('script');
                            for (var j = 0; j < scripts.length; j++) {
                                var old = scripts[j];
                                var fresh = document.createElement('script');
                                for (var k = 0; k < old.attributes.length; k++) {
                                    fresh.setAttribute(old.attributes[k].name, old.attributes[k].value);
                                }
                                if (!old.src) {
                                    fresh.textContent = old.textContent;
                                }
                                old.parentNode.replaceChild(fresh, old);
                            }
                            tpl.parentNode.insertBefore(container, tpl);
                            tpl.parentNode.removeChild(tpl);
                        }
                    }

                    var accepted = null;
                    try {
                        accepted = localStorage.getItem('cookie_consent_accepted');
                    } catch (e) {
                    }

                    if (accepted === version) {
                        activate();
                    } else {
                        document.addEventListener('cookie-consent-accepted', activate);
                    }
                })();
            </script>
            HTML;
    }
}
