<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Auth\CurrentUser;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\UseCases\BulkDeletePostsUseCase;
use Johncms\Modules\Forum\Application\UseCases\GetBulkDeletePostsContextUseCase;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;

final readonly class BulkDeletePostsController
{
    public function __construct(
        private CurrentUser $currentUser,
        private ForumErrorRenderer $forumErrorRenderer,
        private GetBulkDeletePostsContextUseCase $contextUseCase,
        private BulkDeletePostsUseCase $bulkDeletePostsUseCase,
    ) {
    }

    public function __invoke(Request $request, int $id): ViewResponse
    {
        try {
            $backUrl = $this->contextUseCase->execute($id);
        } catch (ForumAccessDeniedException | ForumNotFoundException $exception) {
            return $this->forumErrorRenderer->viewResponse(
                $exception,
                [
                    'back_url'      => '/forum/',
                    'back_url_name' => __('Back'),
                ]
            );
        }

        $ids = $this->extractIds((array) $request->bodyList('post_ids'));
        $confirmIds = $this->extractIds((array) $request->bodyList('ids'));

        if ($request->hasBody('confirm')) {
            if ($confirmIds === []) {
                return $this->result('alert-danger', __('You did not choose something to delete'), $backUrl);
            }

            $this->bulkDeletePostsUseCase->execute($id, $confirmIds, $this->currentUser->user()->name);

            return $this->result('alert-success', __('Marked posts are deleted'), $backUrl);
        }

        if ($ids === []) {
            return $this->result('alert-danger', __('You did not choose something to delete'), $backUrl);
        }

        return new ViewResponse(
            '@forum/public/bulk-delete-posts.twig',
            [
                'title'       => __('Delete posts'),
                'page_title'  => __('Delete posts'),
                'back_url'    => $backUrl,
                'form_action' => '/forum/bulk-delete-posts/' . $id . '/',
                'ids'         => $ids,
            ]
        );
    }

    private function result(string $type, string $message, string $backUrl): ViewResponse
    {
        return new ViewResponse(
            '@theme/pages/result.twig',
            [
                'title'         => __('Delete posts'),
                'page_title'    => __('Delete posts'),
                'type'          => $type,
                'message'       => $message,
                'back_url'      => $backUrl,
                'back_url_name' => __('Back'),
            ]
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
