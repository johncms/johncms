<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\UseCases\ViewPostUseCase;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Application\ForumUtils;
use Johncms\Http\View\ViewResponse;
use Johncms\NavChain;

final readonly class ShowPostController
{
    public function __construct(
        private NavChain $navChain,
        private ForumErrorRenderer $forumErrorRenderer,
        private ViewPostUseCase $viewPostUseCase,
    ) {
    }

    public function __invoke(int $id): ViewResponse
    {
        try {
            $result = $this->viewPostUseCase->execute(
                postId: $id,
                homeUrl: (string) config('johncms.homeurl')
            );
        } catch (ForumNotFoundException | ForumAccessDeniedException $exception) {
            return $this->forumErrorRenderer->viewResponse(
                $exception,
                [
                    'title'         => __('Show post'),
                    'type'          => 'alert-danger',
                    'message'       => __('Wrong data'),
                    'back_url'      => '/forum/',
                    'back_url_name' => __('Forum'),
                ]
            );
        }

        $this->navChain->add(__('Forum'), '/forum/');
        ForumUtils::buildBreadcrumbs(
            (int) $result['topic']->section_id,
            (string) $result['topic']->name,
            (string) $result['topic']->url
        );

        return new ViewResponse(
            '@forum/public/show-post.twig',
            [
                'canonical'   => $result['canonical'],
                'title'       => $this->buildPostMetaTitle($result['post']->id, (string) $result['topic']->name),
                'page_title'  => $this->buildPostMetaTitle($result['post']->id, (string) $result['topic']->name),
                'description' => $this->buildPostMetaDescription(
                    $result['post']->id,
                    (string) $result['topic']->name,
                    (string) ($result['topic']->calculated_meta_description ?? '')
                ),
                'post'        => $result['post'],
                'topic_url'   => $result['topic_url'],
            ]
        );
    }

    private function buildPostMetaTitle(int $postId, string $topicName): string
    {
        return sprintf('%s #%d: %s', __('Show post'), $postId, $topicName);
    }

    private function buildPostMetaDescription(int $postId, string $topicName, string $topicDescription): string
    {
        $postLabel = sprintf('%s #%d', __('Show post'), $postId);

        if ($topicDescription !== '') {
            return sprintf('%s - %s', $topicDescription, $postLabel);
        }

        return sprintf('%s: %s', $postLabel, $topicName);
    }
}
