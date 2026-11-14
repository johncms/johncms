<?php

declare(strict_types=1);

namespace Johncms\Comments\Controllers;

use Johncms\Auth\CurrentUser;
use Johncms\Comments\Application\CommentAccess;
use Johncms\Comments\Application\CommentDiscussionView;
use Johncms\Comments\Application\CommentsService;
use Johncms\Comments\Application\Exceptions\CommentAccessDeniedException;
use Johncms\Comments\Application\Exceptions\CommentNotFoundException;
use Johncms\Comments\Application\Exceptions\CommentSubjectNotFoundException;
use Johncms\Comments\Application\Exceptions\CommentValidationException;
use Johncms\Comments\CommentEditorUploads;
use Johncms\Comments\CommentSubjectDTO;
use Johncms\Exceptions\PageNotFoundException;
use Johncms\Http\EditorImageUploadResponder;
use Johncms\Http\Environment;
use Johncms\Http\ExceptionResponseFactory;
use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\SiteBaseUrl;
use Johncms\Http\View\ViewResponse;
use Johncms\NavChain;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * The discussion of an object of any module. The module declares the routes (CommentRoutes) with
 * the key of its subject, and describes the object through CommentSubjectInterface.
 *
 * A page of the discussion is a live list: the pages of comments and the forms are sent without
 * leaving the page, and the same addresses work as ordinary pages without the script.
 *
 * A subject shown within a page of its module (`embeddedIn`) has no discussion page here: the
 * address of the discussion leads to that page, and the forms come back to it.
 */
final readonly class CommentsController
{
    public function __construct(
        private CommentsService $comments,
        private CommentAccess $access,
        private CommentDiscussionView $view,
        private PaginationGuard $paginationGuard,
        private Session $session,
        private NavChain $navChain,
        private SiteBaseUrl $siteBaseUrl,
        private Environment $environment,
        private CurrentUser $currentUser,
        private ExceptionResponseFactory $exceptionResponses,
    ) {
    }

    public function index(Request $request, string $subject, int $id): Response|ViewResponse
    {
        $object = $this->subject($subject, $id);

        // The addresses of the old engine: its replies are gone, and its offsets counted the
        // pages newest first, so they would land on the wrong page now.
        if ($request->query->has('mod') || $request->query->has('start')) {
            return new RedirectResponse($object->discussionUrl, Response::HTTP_MOVED_PERMANENTLY);
        }

        if ($request->isMethod('POST')) {
            return $this->add($request, $object);
        }

        if ($object->embeddedIn !== '') {
            $page = $request->queryInt('page', 1);

            return new RedirectResponse($this->pageUrl($object, $page) . '#' . CommentDiscussionView::ANCHOR, Response::HTTP_MOVED_PERMANENTLY);
        }

        $pagination = $this->view->pagination($object);
        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            return new RedirectResponse($redirectUrl);
        }

        $total = $pagination->getTotal();
        $meta = new PageMeta(
            sprintf($object->wording->titled, $object->title),
            $pagination->getCurrentPage(),
            $object->description
        );
        $data = [
            'title'       => $meta->title,
            'description' => $meta->description,
        ] + $this->view->data($object, $pagination);

        if ($request->isXmlHttpRequest()) {
            return new ViewResponse('@theme/pages/comments-live.twig', $data);
        }

        $this->breadcrumbs($object);

        return new ViewResponse('@theme/pages/comments.twig', $data + [
            'page_title' => $object->wording->heading,
            'canonical'  => $this->siteBaseUrl->resolve($request) . $this->pageUrl($object, $pagination->getCurrentPage()),
            // A discussion with nothing in it has no content of its own to index.
            'noindex'    => $total === 0,
        ]);
    }

    public function permalink(string $subject, int $id, int $comment): RedirectResponse
    {
        $object = $this->subject($subject, $id);
        try {
            $page = $this->comments->pageOf($object, $comment, $this->perPage());
        } catch (CommentNotFoundException) {
            throw new PageNotFoundException();
        }

        return new RedirectResponse($this->pageUrl($object, $page) . '#comment-' . $comment);
    }

    public function edit(Request $request, string $subject, int $id, int $comment): Response|ViewResponse
    {
        $object = $this->subject($subject, $id);
        try {
            $current = $this->comments->find($object, $comment);
            $this->access->ensureCanEdit($object, $current);
        } catch (CommentNotFoundException) {
            throw new PageNotFoundException();
        } catch (CommentAccessDeniedException $exception) {
            return $this->exceptionResponses->forbidden(message: $exception->getMessage());
        }

        $text = $current->text;
        $error = null;
        if ($request->isMethod('POST')) {
            $text = $request->body('text');
            try {
                $this->comments->edit($object, $comment, $text);
            } catch (CommentNotFoundException) {
                // Deleted by somebody else while it was being edited.
                throw new PageNotFoundException();
            } catch (CommentAccessDeniedException $exception) {
                return $this->exceptionResponses->forbidden(message: $exception->getMessage());
            } catch (CommentValidationException $exception) {
                $error = $exception->getMessage();
            }

            if ($error === null) {
                $this->session->flash(CommentDiscussionView::FLASH_MESSAGE, $object->wording->saved);

                return new RedirectResponse($this->permalinkUrl($object, $comment));
            }
        }

        $this->breadcrumbs($object, linkDiscussion: true);
        $this->navChain->add(d__('system', 'Edit'));

        return new ViewResponse('@theme/pages/comment-edit.twig', [
            'title'      => $object->wording->editTitle,
            'page_title' => $object->wording->editTitle,
            'noindex'    => true,
            'subject'    => $object,
            'comment_id' => $comment,
            'text'       => $text,
            'error'      => $error,
            'action_url' => $object->discussionUrl . '/edit/' . $comment,
            'upload_url' => $object->discussionUrl . '/edit/' . $comment . '/upload',
            'back_url'   => $this->permalinkUrl($object, $comment),
        ]);
    }

    public function delete(Request $request, string $subject, int $id): Response
    {
        $object = $this->subject($subject, $id);
        try {
            $this->comments->delete($object, $request->bodyInt('comment_id'));
        } catch (CommentNotFoundException) {
            throw new PageNotFoundException();
        } catch (CommentAccessDeniedException $exception) {
            return $this->exceptionResponses->forbidden(message: $exception->getMessage());
        }
        $this->session->flash(CommentDiscussionView::FLASH_MESSAGE, $object->wording->deleted);

        return $this->backToPage($object, $request->bodyInt('page'));
    }

    public function deleteAuthor(Request $request, string $subject, int $id): Response
    {
        $object = $this->subject($subject, $id);
        try {
            $count = $this->comments->deleteAuthor($object, $request->bodyInt('comment_id'));
        } catch (CommentNotFoundException) {
            throw new PageNotFoundException();
        } catch (CommentAccessDeniedException $exception) {
            return $this->exceptionResponses->forbidden(message: $exception->getMessage());
        }
        $this->session->flash(
            CommentDiscussionView::FLASH_MESSAGE,
            $object->wording->deletedOfAuthor($count)
        );

        return $this->backToPage($object, $request->bodyInt('page'));
    }

    public function upload(Request $request, EditorImageUploadResponder $responder, string $subject, int $id): JsonResponse
    {
        $object = $this->subject($subject, $id);
        if ($this->access->writeRefusal($object) !== null) {
            return $responder->accessDenied();
        }

        return $responder->storeTracked($request, CommentEditorUploads::OWNER_TYPE, $this->currentUser->id());
    }

    /** A picture added while editing: whoever may edit this comment may add one, in a closed discussion too. */
    public function uploadForEdit(
        Request $request,
        EditorImageUploadResponder $responder,
        string $subject,
        int $id,
        int $comment
    ): JsonResponse {
        $object = $this->subject($subject, $id);
        try {
            if (! $this->access->canEdit($object, $this->comments->find($object, $comment))) {
                return $responder->accessDenied();
            }
        } catch (CommentNotFoundException) {
            throw new PageNotFoundException();
        }

        return $responder->storeTracked($request, CommentEditorUploads::OWNER_TYPE, $this->currentUser->id());
    }

    private function add(Request $request, CommentSubjectDTO $object): RedirectResponse
    {
        $text = $request->body('text');
        try {
            $commentId = $this->comments->add($object, $text, $this->environment->getClientInfo());
        } catch (CommentAccessDeniedException | CommentValidationException $exception) {
            $this->session->flash(CommentDiscussionView::FLASH_ERROR, $exception->getMessage());
            $this->session->flash(CommentDiscussionView::FLASH_TEXT, $text);

            return new RedirectResponse($this->pageUrl($object, max(1, $request->bodyInt('page'))) . '#comment-form');
        }

        $this->session->flash(CommentDiscussionView::FLASH_MESSAGE, $object->wording->added);
        // The anchor of the redirect does not reach the server, so the live list learns the
        // comment to scroll to from here.
        $this->session->flash(CommentDiscussionView::FLASH_ADDED, $commentId);

        return new RedirectResponse($this->pageUrl($object, $this->lastPage($object)) . '#comment-' . $commentId);
    }

    private function backToPage(CommentSubjectDTO $object, int $page): RedirectResponse
    {
        return new RedirectResponse($this->pageUrl($object, min(max(1, $page), $this->lastPage($object))) . '#' . CommentDiscussionView::ANCHOR);
    }

    /**
     * @throws PageNotFoundException
     */
    private function subject(string $type, int $id): CommentSubjectDTO
    {
        try {
            return $this->access->subject($type, $id);
        } catch (CommentSubjectNotFoundException) {
            throw new PageNotFoundException();
        }
    }

    private function breadcrumbs(CommentSubjectDTO $object, bool $linkDiscussion = false): void
    {
        foreach ($object->breadcrumbs as $crumb) {
            $this->navChain->add($crumb['name'], $crumb['url']);
        }
        $this->navChain->add($object->wording->heading, $linkDiscussion ? $object->discussionUrl : '');
    }

    private function permalinkUrl(CommentSubjectDTO $object, int $comment): string
    {
        return $object->discussionUrl . '/comment/' . $comment;
    }

    private function lastPage(CommentSubjectDTO $object): int
    {
        return max(1, (int) ceil($this->comments->count($object) / $this->perPage()));
    }

    private function perPage(): int
    {
        return max(1, (int) $this->currentUser->user()->config->kmess);
    }

    /** A page of the comments: on the page of the discussion, or on the page that shows it within itself. */
    private function pageUrl(CommentSubjectDTO $object, int $page): string
    {
        $url = $object->embeddedIn !== '' ? $object->embeddedIn : $object->discussionUrl;

        return $page > 1 ? $url . '?page=' . $page : $url;
    }
}
