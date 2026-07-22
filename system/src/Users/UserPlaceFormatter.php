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

use Johncms\Utils\PlainTextFormatter;

/**
 * Renders a human readable link for the page a user is currently on.
 */
final class UserPlaceFormatter implements UserPlaceFormatterInterface
{
    /** @var array<string, string>|null */
    private ?array $places = null;

    public function __construct(
        private readonly User $currentUser,
    ) {
    }

    public function format(?string $place): string
    {
        $place = rtrim((string) $place, '/');
        if ($place === '') {
            $place = '/';
        }

        $homeUrl = (string) config('johncms.homeurl', '');
        $places = $this->getPlaces();

        if (array_key_exists($place, $places)) {
            return str_replace('#home#', $homeUrl, $places[$place]);
        }

        $pathWithoutQuery = explode('?', $place)[0];
        if (array_key_exists($pathWithoutQuery, $places)) {
            return str_replace('#home#', $homeUrl, $places[$pathWithoutQuery]);
        }

        $url = $homeUrl . ($this->currentUser->rights >= 6 ? $place : '') . '/';

        return '<a href="' . PlainTextFormatter::escape($url) . '">'
            . d__('system', 'Somewhere on the site')
            . '</a>';
    }

    /**
     * @return array<string, string>
     */
    private function getPlaces(): array
    {
        if ($this->places !== null) {
            return $this->places;
        }

        $places = require CONFIG_PATH . 'places.global.php';

        $localFile = CONFIG_PATH . 'places.local.php';
        if (is_file($localFile)) {
            $places = array_merge($places, require $localFile);
        }

        $this->places = $places;

        return $this->places;
    }
}
