<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Forum\Application\Exceptions\AccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\CuratorsTopicNotFoundException;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Services\ForumAccessResponseBuilder;
use Johncms\Modules\Forum\Application\UseCases\EnsureCuratorsAccessUseCase;
use Johncms\Modules\Forum\Application\UseCases\EnsureForumAccessUseCase;
use Johncms\Modules\Forum\Application\UseCases\GetCuratorsContextUseCase;
use Johncms\Modules\Forum\Application\UseCases\UpdateCuratorsUseCase;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;

final readonly class CuratorsController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private EnsureForumAccessUseCase $forumAccessUseCase,
        private ForumAccessResponseBuilder $forumAccessResponseBuilder,
        private EnsureCuratorsAccessUseCase $accessUseCase,
        private GetCuratorsContextUseCase $contextUseCase,
        private UpdateCuratorsUseCase $updateCuratorsUseCase,
    ) {
        $this->controllerContext->initModule('forum');
    }

    public function __invoke(int $id): string
    {
        try {
            $this->forumAccessUseCase->execute();
        } catch (ForumAccessDeniedException $exception) {
            return $this->render->render(
                'system::pages/result',
                $this->forumAccessResponseBuilder->forException($exception)
            );
        }

        try {
            $this->accessUseCase->execute();
            $context = $this->contextUseCase->execute($id);
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
        } catch (CuratorsTopicNotFoundException) {
            return $this->render->render(
                'system::pages/result',
                [
                    'title'         => __('Curators'),
                    'page_title'    => __('Curators'),
                    'type'          => 'alert-danger',
                    'message'       => __('Topic has been deleted or does not exists'),
                    'back_url'      => '/forum/',
                    'back_url_name' => __('Back'),
                ]
            );
        }

        $topic = $context->topic;
        $start = (int) $this->request->getQuery('start', 0);
        $total = count($context->candidates);

        $selectedUsers = ! empty($topic->curators) ? $topic->curators : [];
        $saved = false;

        if ($this->request->getPost('submit') !== null) {
            $selectedUsers = $this->request->getPost('users', []);
            if (! is_array($selectedUsers)) {
                $selectedUsers = [];
            }
        }

        $curatorsList = [];
        $curators = [];
        foreach ($context->candidates as $candidate) {
            $checked = array_key_exists($candidate['user_id'], $selectedUsers);
            if ($checked) {
                $curators[$candidate['user_id']] = $candidate['user_name'];
            }

            $curatorsList[] = [
                'user_id'   => $candidate['user_id'],
                'user_name' => $candidate['user_name'],
                'checked'   => $checked,
            ];
        }

        if ($this->request->getPost('submit') !== null && $total > 0) {
            $this->updateCuratorsUseCase->execute($topic, $curators);
            $saved = true;
        }

        return $this->render->render(
            'forum::curators',
            [
                'title'         => __('Curators'),
                'page_title'    => __('Curators'),
                'id'            => $topic->id,
                'start'         => $start,
                'back_url'      => '/forum/?type=topic&id=' . $topic->id . '&amp;start=' . $start,
                'total'         => $total,
                'curators_list' => $curatorsList,
                'topic'         => $topic,
                'saved'         => $saved,
            ]
        );
    }
}
