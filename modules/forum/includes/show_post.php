<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Johncms\Modules\Forum\Application\Exceptions\AccessDeniedException;
use Johncms\Modules\Forum\Application\UseCases\ViewPostUseCase;
use Johncms\Modules\Forum\Domain\Exceptions\MessageNotFoundException;

defined('_IN_JOHNCMS') || die('Error: restricted access');

$request = di(\Johncms\System\Http\Request::class);
$useCase = di(ViewPostUseCase::class);

$postId = (int) $request->getQuery('id');
$start = (int) $request->getQuery('start', 0);

try {
    $result = $useCase->execute($postId, $start, $set_forum, $config['homeurl']);
} catch (MessageNotFoundException | AccessDeniedException $exception) {
    http_response_code(404);
    echo $view->render(
        'system::pages/result',
        [
            'title'         => __('Show post'),
            'type'          => 'alert-danger',
            'message'       => __('Wrong data'),
            'back_url'      => '/forum/',
            'back_url_name' => __('Forum'),
        ]
    );
    exit;
}

$post = $result['post'];
$topic = $result['topic'];
$canonical = $result['canonical'];

$view->addData(
    [
        'canonical'  => $canonical,
        'title'      => __('Show post'),
        'page_title' => __('Show post'),
    ]
);

echo $view->render(
    'forum::show_post',
    [
        'post'          => $post,
        'topic'         => $topic,
    ]
);
