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

use Johncms\Cache\CacheInterface;
use PDO;
use Symfony\Component\HttpFoundation\RequestStack;

class Counters
{
    /** Default counters cache lifetime, in seconds */
    private const CACHE_TTL = 600;

    /** Online counters cache lifetime, in seconds */
    private const ONLINE_CACHE_TTL = 10;

    /** Tag every counter is filed under, so that all of them can be invalidated at once */
    private const CACHE_TAG = 'counters';

    /** @var PDO */
    private $db;

    /** @var CacheInterface */
    private $cache;

    /** @var RequestStack */
    private $requestStack;

    public function __construct(
        PDO $pdo,
        CacheInterface $cache,
        RequestStack $requestStack
    ) {
        $this->db = $pdo;
        $this->cache = $cache;
        $this->requestStack = $requestStack;
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
