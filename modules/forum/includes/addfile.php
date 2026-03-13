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
use Johncms\Modules\Forum\Application\Exceptions\UploadException;
use Johncms\Modules\Forum\Application\Exceptions\UploadExpiredException;
use Johncms\Modules\Forum\Application\UseCases\AttachFileToPostUseCase;
use Johncms\Modules\Forum\Application\UseCases\EnsureAttachFileAccessUseCase;
use Johncms\Modules\Forum\Application\UseCases\GetAttachFileContextUseCase;
use Johncms\Modules\Forum\Domain\Exceptions\MessageNotFoundException;
use Johncms\System\Http\Request;

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var array $config
 */

/** @var Request $request */
$request = di(Request::class);

$page = (int) $request->getQuery('page', 1);
try {
    /** @var EnsureAttachFileAccessUseCase $accessUseCase */
    $accessUseCase = di(EnsureAttachFileAccessUseCase::class);
    $accessUseCase->execute($id, $page);

    /** @var GetAttachFileContextUseCase $contextUseCase */
    $contextUseCase = di(GetAttachFileContextUseCase::class);
    $context = $contextUseCase->execute($id, $page);
} catch (AccessDeniedException $exception) {
    http_response_code(403);
    echo $view->render(
        'system::pages/result',
        [
            'title'         => __('Access forbidden'),
            'type'          => 'alert-danger',
            'message'       => __('Access forbidden'),
            'back_url'      => '/forum/',
            'back_url_name' => __('Back'),
        ]
    );
    exit;
} catch (MessageNotFoundException $exception) {
    echo $view->render(
        'system::pages/result',
        [
            'title'         => __('Wrong data'),
            'type'          => 'alert-danger',
            'message'       => __('Wrong data'),
            'back_url'      => '/forum/',
            'back_url_name' => __('Back'),
        ]
    );
    exit;
} catch (UploadExpiredException $exception) {
    echo $view->render(
        'system::pages/result',
        [
            'title'         => __('Add file'),
            'type'          => 'alert-danger',
            'message'       => __('The time allotted for the file upload has expired'),
            'back_url'      => '/forum/?&typ=topic&id=' . $exception->getTopicId() . '&amp;page=' . $exception->getPage(),
            'back_url_name' => __('Back'),
        ]
    );
    exit;
}

$file_attached = false;
$topicId = $context->topicId;
$page = $context->page;

if ($request->getMethod() === 'POST') {
    /** @var AttachFileToPostUseCase $useCase */
    $useCase = di(AttachFileToPostUseCase::class);

    try {
        $result = $useCase->execute(
            messageId: $id,
            extensions: config('forum')['extensions'],
            maxFileSizeKb: (int) $config['flsz'],
            uploadedFiles: $request->getUploadedFiles(),
        );
    } catch (MessageNotFoundException $exception) {
        echo $view->render(
            'system::pages/result',
            [
                'title'         => __('Wrong data'),
                'type'          => 'alert-danger',
                'message'       => __('Wrong data'),
                'back_url'      => '/forum/',
                'back_url_name' => __('Back'),
            ]
        );
        exit;
    } catch (UploadException $exception) {
        echo $view->render(
            'system::pages/result',
            [
                'title'         => __('Add file'),
                'page_title'    => __('Error uploading file'),
                'type'          => 'alert-danger',
                'message'       => $exception->getErrors() ?: __('Error uploading file'),
                'back_url'      => '/forum/?act=addfile&id=' . $id,
                'back_url_name' => __('Repeat'),
            ]
        );
        exit;
    }

    $file_attached = $result->fileAttached;
    $page = $result->page;
    $topicId = $result->topicId;
}

echo $view->render(
    'forum::add_file',
    [
        'title'         => __('Add File'),
        'page_title'    => __('Add File'),
        'id'            => $id,
        'file_attached' => $file_attached,
        'topic_id'      => $topicId,
        'back_url'      => '?type=topic&id=' . $topicId . '&amp;page=' . $page,
    ]
);
