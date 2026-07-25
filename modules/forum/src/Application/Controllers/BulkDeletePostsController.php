<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\UseCases\BulkDeletePostsUseCase;
use Johncms\Modules\Forum\Application\UseCases\GetBulkDeletePostsContextUseCase;
use Johncms\Security\Csrf;
use Johncms\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Johncms\Validator\Validator;
use Symfony\Component\HttpFoundation\Response;

final readonly class BulkDeletePostsController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private User $currentUser,
        private Csrf $csrf,
        private ForumErrorRenderer $forumErrorRenderer,
        private GetBulkDeletePostsContextUseCase $contextUseCase,
        private BulkDeletePostsUseCase $bulkDeletePostsUseCase,
    ) {
        $this->controllerContext->initModule('forum');
    }

    public function __invoke(int $id): Response
    {
        try {
            $backUrl = $this->contextUseCase->execute($id);
        } catch (ForumAccessDeniedException | ForumNotFoundException $exception) {
            return $this->forumErrorRenderer->render(
                $this->render,
                $exception,
                [
                    'back_url'      => '/forum/',
                    'back_url_name' => __('Back'),
                ]
            );
        }

        $ids = $this->extractIds((array) $this->request->bodyList('post_ids'));
        $confirmIds = $this->extractIds((array) $this->request->bodyList('ids'));

        if ($this->request->hasBody('confirm')) {
            $validator = new Validator(
                ['csrf_token' => $this->request->body('csrf_token', '')],
                ['csrf_token' => ['Csrf']]
            );

            if (! $validator->isValid()) {
                return new Response(
                    $this->render->render(
                        'system::pages/result',
                        [
                            'title'         => __('Delete posts'),
                            'page_title'    => __('Delete posts'),
                            'type'          => 'alert-danger',
                            'message'       => __('Wrong data'),
                            'back_url'      => $backUrl,
                            'back_url_name' => __('Back'),
                        ]
                    )
                );
            }

            if ($confirmIds === []) {
                return new Response(
                    $this->render->render(
                        'system::pages/result',
                        [
                            'title'         => __('Delete posts'),
                            'page_title'    => __('Delete posts'),
                            'type'          => 'alert-danger',
                            'message'       => __('You did not choose something to delete'),
                            'back_url'      => $backUrl,
                            'back_url_name' => __('Back'),
                        ]
                    )
                );
            }

            $this->bulkDeletePostsUseCase->execute($id, $confirmIds, $this->currentUser->name);

            return new Response(
                $this->render->render(
                    'system::pages/result',
                    [
                        'title'         => __('Delete posts'),
                        'page_title'    => __('Delete posts'),
                        'type'          => 'alert-success',
                        'message'       => __('Marked posts are deleted'),
                        'back_url'      => $backUrl,
                        'back_url_name' => __('Back'),
                    ]
                )
            );
        }

        if ($ids === []) {
            return new Response(
                $this->render->render(
                    'system::pages/result',
                    [
                        'title'         => __('Delete posts'),
                        'page_title'    => __('Delete posts'),
                        'type'          => 'alert-danger',
                        'message'       => __('You did not choose something to delete'),
                        'back_url'      => $backUrl,
                        'back_url_name' => __('Back'),
                    ]
                )
            );
        }

        return new Response(
            $this->render->render(
                'forum::mass_delete',
                [
                    'title'       => __('Delete posts'),
                    'page_title'  => __('Delete posts'),
                    'back_url'    => $backUrl,
                    'form_action' => '/forum/bulk-delete-posts/' . $id . '/',
                    'csrf_token'  => $this->csrf->getToken(),
                    'ids'         => $ids,
                ]
            )
        );
    }

    /**
     * @param array<int, mixed> $rawIds
     * @return int[]
     */
    private function extractIds(array $rawIds): array
    {
        $ids = [];
        foreach ($rawIds as $value) {
            $id = filter_var($value, FILTER_VALIDATE_INT);
            if ($id !== false && $id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }
}
