<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Johncms\NavChain;
use Johncms\System\Http\Request;

defined('_IN_JOHNADM') || die('Error: restricted access');

/**
 * @var NavChain $nav_chain
 * @var Johncms\System\Users\User $user
 * @var Johncms\System\View\Render $view
 */

if ($user->rights < 9) {
    exit(__('Access denied'));
}

/** @var Request $request */
$request = di(Request::class);

$forum_config = di('config')['forum'];
$nav_chain->add(__('Forum Settings'));

if ($request->getMethod() === 'POST') {
    // Save forum settings
    $settings = [
        'file_counters'       => $request->getPost('file_counters', false, FILTER_VALIDATE_INT),
        'topic_keywords'      => $request->getPost('topic_keywords', '', FILTER_SANITIZE_STRING),
        'topic_description'   => $request->getPost('topic_description', '', FILTER_SANITIZE_STRING),
        'section_keywords'    => $request->getPost('section_keywords', '', FILTER_SANITIZE_STRING),
        'section_description' => $request->getPost('section_description', '', FILTER_SANITIZE_STRING),
        'forum_keywords'      => $request->getPost('forum_keywords', '', FILTER_SANITIZE_STRING),
        'forum_description'   => $request->getPost('forum_description', '', FILTER_SANITIZE_STRING),
    ];
    $forum_config['settings'] = $settings;

    $configFile = "<?php\n\n" . 'return ' . var_export(['forum' => $forum_config], true) . ";\n";
    if (! file_put_contents(CONFIG_PATH . 'autoload/forum.local.php', $configFile)) {
        echo 'ERROR: Can not write forum.local.php';
        exit;
    }

    $confirmation = true;
    if (function_exists('opcache_reset')) {
        opcache_reset();
    }
}

echo $view->render(
    'admin::forum/settings',
    [
        'confirmation' => $confirmation ?? false,
        'forum_config' => $forum_config['settings'],
    ]
);
