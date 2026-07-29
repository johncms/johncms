<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\UseCases\GetCuratorsContextUseCase;
use Johncms\Modules\Forum\Application\UseCases\UpdateCuratorsUseCase;
use Johncms\Http\Request;
use Johncms\System\View\Render;
use Symfony\Component\HttpFoundation\Response;

final readonly class CuratorsController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private ForumErrorRenderer $forumErrorRenderer,
        private GetCuratorsContextUseCase $contextUseCase,
        private UpdateCuratorsUseCase $updateCuratorsUseCase,
    ) {
        $this->controllerContext->initModule('forum');
    }

    public function __invoke(Request $request, int $id): Response
    {
        try {
            $context = $this->contextUseCase->execute($id);
        } catch (ForumAccessDeniedException $exception) {
            return $this->forumErrorRenderer->render(
                $this->render,
                $exception,
                [
                    'back_url'      => '/forum/',
                    'back_url_name' => __('Back'),
                ]
            );
        } catch (ForumNotFoundException $exception) {
            return $this->forumErrorRenderer->render(
                $this->render,
                $exception,
                [
                    'title'         => __('Curators'),
                    'page_title'    => __('Curators'),
                    'message'       => __('Topic has been deleted or does not exists'),
                    'back_url'      => '/forum/',
                    'back_url_name' => __('Back'),
                ]
            );
        }

        $topic = $context['topic'];
        $page = max(1, $request->queryInt('page', 1));
        $total = count($context['candidates']);

        $selectedUsers = ! empty($topic->curators) ? $topic->curators : [];
        $saved = false;

        if ($request->hasBody('submit')) {
            $selectedUsers = $request->bodyList('users');
            if (! is_array($selectedUsers)) {
                $selectedUsers = [];
            }
        }

        $curatorsList = [];
        $curators = [];
        foreach ($context['candidates'] as $candidate) {
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

        if ($request->hasBody('submit') && $total > 0) {
            $this->updateCuratorsUseCase->execute($topic, $curators);
            $saved = true;
        }

        return new Response(
            $this->render->render(
                'forum::curators',
                [
                    'title'         => __('Curators'),
                    'page_title'    => __('Curators'),
                    'id'            => $topic->id,
                    'page'          => $page,
                    'back_url'      => $topic->url . ($page > 1 ? '?page=' . $page : ''),
                    'total'         => $total,
                    'curators_list' => $curatorsList,
                    'topic'         => $topic,
                    'saved'         => $saved,
                ]
            )
        );
    }
}
