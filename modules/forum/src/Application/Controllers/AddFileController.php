<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Forum\Application\Exceptions\AccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\UploadException;
use Johncms\Modules\Forum\Application\Exceptions\UploadExpiredException;
use Johncms\Modules\Forum\Application\Services\ForumAccessResponseBuilder;
use Johncms\Modules\Forum\Application\UseCases\AttachFileToPostUseCase;
use Johncms\Modules\Forum\Application\UseCases\EnsureForumAccessUseCase;
use Johncms\Modules\Forum\Application\UseCases\EnsureAttachFileAccessUseCase;
use Johncms\Modules\Forum\Application\UseCases\GetAttachFileContextUseCase;
use Johncms\Modules\Forum\Domain\Exceptions\MessageNotFoundException;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;

final readonly class AddFileController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private EnsureForumAccessUseCase $forumAccessUseCase,
        private ForumAccessResponseBuilder $forumAccessResponseBuilder,
        private EnsureAttachFileAccessUseCase $accessUseCase,
        private GetAttachFileContextUseCase $contextUseCase,
        private AttachFileToPostUseCase $attachFileToPostUseCase,
    ) {
        $this->controllerContext->initModule('forum');
    }

    public function __invoke(int $id): string
    {
        $config = config('johncms');
        $forumConfig = config('forum');
        $page = (int) $this->request->getQuery('page', 1);

        try {
            $this->forumAccessUseCase->execute();
        } catch (ForumAccessDeniedException $exception) {
            return $this->render->render(
                'system::pages/result',
                $this->forumAccessResponseBuilder->forException($exception)
            );
        }

        try {
            $this->accessUseCase->execute($id, $page);
            $context = $this->contextUseCase->execute($id, $page);
        } catch (AccessDeniedException) {
            http_response_code(403);
            return $this->render->render(
                'system::pages/result',
                [
                    'title'         => __('Access forbidden'),
                    'type'          => 'alert-danger',
                    'message'       => __('Access forbidden'),
                    'back_url'      => '/forum/',
                    'back_url_name' => __('Back'),
                ]
            );
        } catch (MessageNotFoundException) {
            return $this->render->render(
                'system::pages/result',
                [
                    'title'         => __('Wrong data'),
                    'type'          => 'alert-danger',
                    'message'       => __('Wrong data'),
                    'back_url'      => '/forum/',
                    'back_url_name' => __('Back'),
                ]
            );
        } catch (UploadExpiredException $exception) {
            return $this->render->render(
                'system::pages/result',
                [
                    'title'         => __('Add file'),
                    'type'          => 'alert-danger',
                    'message'       => __('The time allotted for the file upload has expired'),
                    'back_url'      => '/forum/?type=topic&id=' . $exception->getTopicId() . '&amp;page=' . $exception->getPage(),
                    'back_url_name' => __('Back'),
                ]
            );
        }

        $fileAttached = false;
        $topicId = $context->topicId;
        $page = $context->page;

        if ($this->request->getMethod() === 'POST') {
            try {
                $result = $this->attachFileToPostUseCase->execute(
                    messageId: $id,
                    extensions: $forumConfig['extensions'],
                    maxFileSizeKb: (int) $config['flsz'],
                    uploadedFiles: $this->request->getUploadedFiles(),
                );
            } catch (MessageNotFoundException) {
                return $this->render->render(
                    'system::pages/result',
                    [
                        'title'         => __('Wrong data'),
                        'type'          => 'alert-danger',
                        'message'       => __('Wrong data'),
                        'back_url'      => '/forum/',
                        'back_url_name' => __('Back'),
                    ]
                );
            } catch (UploadException $exception) {
                return $this->render->render(
                    'system::pages/result',
                    [
                        'title'         => __('Add file'),
                        'page_title'    => __('Error uploading file'),
                        'type'          => 'alert-danger',
                        'message'       => $exception->getErrors() ?: __('Error uploading file'),
                        'back_url'      => '/forum/addfile/' . $id . '/',
                        'back_url_name' => __('Repeat'),
                    ]
                );
            }

            $fileAttached = $result->fileAttached;
            $page = $result->page;
            $topicId = $result->topicId;
        }

        return $this->render->render(
            'forum::add_file',
            [
                'title'         => __('Add File'),
                'page_title'    => __('Add File'),
                'id'            => $id,
                'file_attached' => $fileAttached,
                'topic_id'      => $topicId,
                'back_url'      => '/forum/?type=topic&id=' . $topicId . '&amp;page=' . $page,
                'config'        => $config,
            ]
        );
    }
}
