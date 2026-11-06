<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Controllers;

use Johncms\Http\EditorImageUploadResponder;
use Johncms\Http\Environment;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Request;
use Johncms\Modules\News\Application\Collections\NewsDiscussionServiceInterface;
use Johncms\Modules\News\Application\DTO\NewsCommentViewDTO;
use Johncms\Modules\News\Application\Exceptions\NewsDiscussionAccessDeniedException;
use Johncms\Modules\News\Application\Exceptions\NewsDiscussionValidationException;
use Johncms\Modules\News\Application\Exceptions\NewsItemNotFoundException;
use Johncms\Users\ProfileLinksInterface;
use Johncms\View\Twig\Runtime\AssetRuntime;
use JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;

final readonly class CommentsController
{
    public function __construct(
        private NewsDiscussionServiceInterface $discussion,
        private PaginationFactory $paginationFactory,
        private ProfileLinksInterface $profiles,
        private AssetRuntime $assets,
    ) {
    }

    public function index(int $article_id): JsonResponse
    {
        return $this->respond(function () use ($article_id): JsonResponse {
            $first = $this->discussion->comments($article_id, 1);
            $pager = $this->paginationFactory->create($first->total);
            $page = $this->discussion->comments($article_id, $pager->getPerPage(), $pager->getOffset());
            return new JsonResponse([
                'current_page' => $pager->getCurrentPage(), 'data' => array_map($this->comment(...), $page->comments),
                'from' => $page->total > 0 ? $pager->getOffset() + 1 : null, 'last_page' => $pager->getTotalPages(),
                'next_page_url' => $pager->getCurrentPage() < $pager->getTotalPages() ? $pager->getUrl($pager->getCurrentPage() + 1) : null,
                'per_page' => $pager->getPerPage(), 'prev_page_url' => $pager->getCurrentPage() > 1 ? $pager->getUrl($pager->getCurrentPage() - 1) : null,
                'to' => $page->total > 0 ? $pager->getOffset() + count($page->comments) : null, 'total' => $page->total,
            ]);
        });
    }

    public function add(int $article_id, Request $request, Environment $env): JsonResponse
    {
        return $this->respond(function () use ($article_id, $request, $env): JsonResponse {
            $data = $this->decode($request);
            $text = $data['comment'] ?? '';
            $files = $data['attached_files'] ?? [];
            if (! is_string($text) || ! is_array($files) || ! array_is_list($files)) {
                throw new NewsDiscussionValidationException(d__('news', 'Bad Request'));
            }
            $this->discussion->addComment($article_id, $text, $files, ['user_agent' => $env->getUserAgent(), 'ip' => $env->getIp(false), 'ip_via_proxy' => $env->getIpViaProxy(false)]);
            $total = $this->discussion->comments($article_id, 1)->total;
            return new JsonResponse(['message' => d__('news', 'The comment was added successfully'), 'last_page' => $this->paginationFactory->create($total)->getTotalPages()]);
        });
    }

    public function del(Request $request): JsonResponse
    {
        return $this->respond(function () use ($request): JsonResponse {
            $id = $this->decode($request)['comment_id'] ?? null;
            if (! is_int($id) || $id < 1) {
                throw new NewsDiscussionValidationException(d__('news', 'Bad Request'));
            }
            $this->discussion->deleteComment($id);
            return new JsonResponse(['message' => d__('news', 'The comment was deleted successfully')]);
        });
    }

    public function loadFile(Request $request, EditorImageUploadResponder $responder): JsonResponse
    {
        return $responder->store($request, 'news_comments', 'news');
    }

    /** @return array<string, mixed> */
    private function comment(NewsCommentViewDTO $comment): array
    {
        $author = $comment->author;
        $data = [
            'id' => $comment->id, 'created_at' => $comment->createdAt, 'text' => (string) $comment->text,
            'user' => ['id' => $author?->id, 'user_name' => $author !== null ? $author->name : '',
                'status' => $author !== null ? $author->status : '', 'is_online' => $author !== null && $author->online, 'rights_name' => $author !== null ? $author->rightsName : '',
                'avatar' => $this->assets->avatar($author?->id), 'profile_url' => $author !== null ? ($this->profiles->urlOf($author->id) ?? '') : ''],
            'can_delete' => $comment->canDelete, 'can_reply' => $comment->canReply, 'can_quote' => $comment->canReply,
        ];
        if ($comment->moderatorData !== []) {
            $data += $comment->moderatorData;
            $data['search_ip_url'] = '/admin/ip-search?ip=' . rawurlencode((string) ($comment->moderatorData['ip'] ?? ''));
            $data['search_ip_via_proxy_url'] = '/admin/ip-search?ip=' . rawurlencode((string) ($comment->moderatorData['ip_via_proxy'] ?? ''));
        }
        return $data;
    }

    /** @return array<string, mixed> */
    private function decode(Request $request): array
    {
        try {
            $data = json_decode(json: $request->getContent(), associative: true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new NewsDiscussionValidationException(d__('news', 'Bad Request'));
        }
        if (! is_array($data)) {
            throw new NewsDiscussionValidationException(d__('news', 'Bad Request'));
        }
        return $data;
    }

    /** @param callable(): JsonResponse $operation */
    private function respond(callable $operation): JsonResponse
    {
        try {
            return $operation();
        } catch (NewsDiscussionAccessDeniedException $exception) {
            return new JsonResponse(['message' => $exception->getMessage()], 403);
        } catch (NewsItemNotFoundException $exception) {
            return new JsonResponse(['message' => $exception->getMessage()], 404);
        } catch (NewsDiscussionValidationException $exception) {
            return new JsonResponse(['message' => $exception->getMessage()], 422);
        }
    }
}
