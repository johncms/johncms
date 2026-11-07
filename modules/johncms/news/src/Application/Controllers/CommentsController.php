<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Controllers;

use Johncms\Http\EditorImageUploadResponder;
use Johncms\Http\Environment;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Modules\News\Application\Collections\NewsDiscussionServiceInterface;
use Johncms\Modules\News\Application\Collections\NewsReadServiceInterface;
use Johncms\Modules\News\Application\Exceptions\NewsDiscussionAccessDeniedException;
use Johncms\Modules\News\Application\Exceptions\NewsDiscussionValidationException;
use Johncms\Modules\News\Application\Exceptions\NewsItemNotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;

/** Comment forms of the article page; every action answers with a redirect back to the article. */
final readonly class CommentsController
{
    /** Flash keys the article page reads after a redirect. */
    public const string MESSAGE = 'news_comment_message';
    public const string ERROR = 'news_comment_error';
    public const string TEXT = 'news_comment_text';

    public function __construct(
        private NewsDiscussionServiceInterface $discussion,
        private NewsReadServiceInterface $news,
        private PaginationFactory $paginationFactory,
        private Session $session,
    ) {
    }

    public function add(int $article_id, Request $request, Environment $env): never
    {
        $url = $this->articleUrl($article_id);
        $text = $request->body('text');
        try {
            $files = [];
            foreach ($request->bodyList('attached_files') as $file) {
                if (! is_string($file) || ! ctype_digit($file) || strlen($file) > 10) {
                    throw new NewsDiscussionValidationException(d__('news', 'One of the comment attachments is not available.'));
                }
                $files[] = (int) $file;
            }
            $id = $this->discussion->addComment(
                itemId:   $article_id,
                text:     $text,
                files:    $files,
                metadata: ['user_agent' => $env->getUserAgent(), 'ip' => $env->getIp(false), 'ip_via_proxy' => $env->getIpViaProxy(false)],
            );
        } catch (NewsDiscussionAccessDeniedException | NewsDiscussionValidationException | NewsItemNotFoundException $exception) {
            $this->session->flash(self::ERROR, $exception->getMessage());
            $this->session->flash(self::TEXT, $text);
            redirect($url . '#comment-form');
        }
        $this->session->flash(self::MESSAGE, d__('news', 'The comment was added successfully'));
        redirect($this->pageUrl($url, $this->lastPage($article_id)) . '#comment-' . $id);
    }

    public function del(Request $request): never
    {
        $id = $request->bodyInt('comment_id');
        $page = max(1, $request->bodyInt('page'));
        try {
            $articleId = $this->discussion->deleteComment($id);
        } catch (NewsDiscussionAccessDeniedException | NewsDiscussionValidationException | NewsItemNotFoundException $exception) {
            $this->session->flash(self::ERROR, $exception->getMessage());
            redirect($this->articleUrl($request->bodyInt('article_id')) . '#article-comments');
        }
        $this->session->flash(self::MESSAGE, d__('news', 'The comment was deleted successfully'));
        $article = $this->news->findById($articleId);
        if ($article === null) {
            redirect('/news/');
        }
        redirect($this->pageUrl($article->url, min($page, max(1, $this->lastPage($articleId)))) . '#article-comments');
    }

    public function loadFile(Request $request, EditorImageUploadResponder $responder): JsonResponse
    {
        return $responder->store($request, 'news_comments', 'news');
    }

    private function articleUrl(int $articleId): string
    {
        return $this->news->findById($articleId)->url ?? '/news/';
    }

    private function lastPage(int $articleId): int
    {
        return $this->paginationFactory->create($this->discussion->comments($articleId, 1)->total)->getTotalPages();
    }

    private function pageUrl(string $url, int $page): string
    {
        return $page > 1 ? $url . '?page=' . $page : $url;
    }
}
