<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Illuminate\Pagination\LengthAwarePaginator;
use Johncms\Modules\Forum\Application\Services\ForumVisitorPlaceFormatter;
use Johncms\System\i18n\Translator;
use Johncms\Users\User;

defined('_IN_JOHNCMS') || die('Error: restricted access');

/** @var Johncms\System\Http\Environment $env */
$env = di(Johncms\System\Http\Environment::class);

$forumPlaceFormatter = null;
try {
    $forumPlaceFormatter = di(ForumVisitorPlaceFormatter::class);
    di(Translator::class)->addTranslationDomain('forum', MODULES_PATH . 'forum/locale', false);
} catch (Throwable) {
}

$data = [];
$data['filters'] = [
    'users'   => [
        'name'   => __('Users'),
        'url'    => '/online/',
        'active' => false,
    ],
    'history' => [
        'name'   => __('History'),
        'url'    => '/online/history/',
        'active' => true,
    ],
];

if ($user->rights) {
    $data['filters']['guest'] = [
        'name'   => __('Guests'),
        'url'    => '/online/guest/',
        'active' => false,
    ];
    $data['filters']['ip'] = [
        'name'   => __('IP Activity'),
        'url'    => '/online/ip/',
        'active' => false,
    ];
}
/** @var LengthAwarePaginator $users */
$users = (new User())
    ->whereBetween('lastdate', [(time() - 172800), (time() - 310)])
    ->orderBy('lastdate', 'desc')
    ->paginate($user->config->kmess);

$total = $users->total();

if ($total) {
    $items = $users->getItems()->map(
        static function ($user) use ($tools, $forumPlaceFormatter) {
            /** @var $user User */
            $place = (string) $user->place;
            $user->place_name = $forumPlaceFormatter !== null && str_starts_with($place, '/forum')
                ? $forumPlaceFormatter->format($place)
                : $tools->displayPlace($place);
            $user->display_date = $tools->displayDate($user->sestime);
            return $user;
        }
    );
}

$data['pagination'] = $users->render();
$data['total'] = $total;
$data['items'] = $items ?? [];

echo $view->render(
    'online::users',
    [
        'title'      => $title,
        'page_title' => $title,
        'data'       => $data,
    ]
);
