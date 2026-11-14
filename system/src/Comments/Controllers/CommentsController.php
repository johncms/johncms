<?php

declare(strict_types=1);

namespace Johncms\Comments\Controllers;

use Johncms\Auth\CurrentUser;
use Johncms\Comments\Application\CommentAccess;
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
use Johncms\Http\Pagination\PaginationFactory;
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
 */
final readonly class CommentsController
{
    public const string FLASH_MESSAGE = 'comments.message';
    public const string FLASH_ERROR = 'comments.error';
    public const string FLASH_TEXT = 'comments.text';
    public const string FLASH_ADDED = 'comments.added';

    /** Id of the discussion on the page; the links of the pagination lead to it. */
    private const string ANCHOR = 'comments';

    public function __construct(
        private CommentsService $comments,
        private CommentAccess $access,
        private PaginationFactory $paginationFactory,
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

        $total = $this->comments->count($object);
        $pagination = $this->paginationFactory->create(total: $total, fragment: self::ANCHOR);
        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            return new RedirectResponse($redirectUrl);
        }

        $page = $this->comments->page($object, $pagination->getPerPage(), $pagination->getOffset());
        $this->comments->viewed($object);
        $meta = new PageMeta(
            $object->heading === '' ? sprintf(d__('system', 'Comments: %s'), $object->title) : $object->heading . ': ' . $object->title,
            $pagination->getCurrentPage(),
            $object->description
        );
        $data = [
            'title'          => $meta->title,
            'description'    => $meta->description,
            'subject'        => $object,
            'comments'       => $page->comments,
            'total'          => $total,
            'page'           => $pagination->getCurrentPage(),
            'pagination'     => $pagination->hasPages() ? $pagination->render() : null,
            'can_write'      => $page->canWrite,
            'refusal'        => $this->currentUser->isValid() ? $page->refusal : '',
            'upload_url'     => $object->discussionUrl . '/upload',
            'message'        => $this->session->getFlash(self::FLASH_MESSAGE),
            'error'          => $this->session->getFlash(self::FLASH_ERROR),
            'added'          => $this->session->getFlash(self::FLASH_ADDED),
            // The live list keeps the text in the editor; the flash is still read so that it does
            // not come back on the next page shown.
            'text'           => $this->session->getFlash(self::FLASH_TEXT) ?? '',
        ];

        if ($request->isXmlHttpRequest()) {
            return new ViewResponse('@theme/pages/comments-live.twig', $data);
        }

        $this->breadcrumbs($object);

        return new ViewResponse('@theme/pages/comments.twig', $data + [
            'page_title' => $object->heading ?: d__('system', 'Comments'),
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
                $this->session->flash(self::FLASH_MESSAGE, d__('system', 'The comment was saved'));

                return new RedirectResponse($this->permalinkUrl($object, $comment));
            }
        }

        $this->breadcrumbs($object, linkDiscussion: true);
        $this->navChain->add(d__('system', 'Edit'));

        return new ViewResponse('@theme/pages/comment-edit.twig', [
            'title'      => d__('system', 'Edit comment'),
            'page_title' => d__('system', 'Edit comment'),
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
        $this->session->flash(self::FLASH_MESSAGE, d__('system', 'The comment was deleted'));

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
            self::FLASH_MESSAGE,
            sprintf(dn__('system', '%d comment was deleted', '%d comments were deleted', $count), $count)
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
            $this->session->flash(self::FLASH_ERROR, $exception->getMessage());
            $this->session->flash(self::FLASH_TEXT, $text);

            return new RedirectResponse($this->pageUrl($object, max(1, $request->bodyInt('page'))) . '#comment-form');
        }

        $this->session->flash(self::FLASH_MESSAGE, d__('system', 'The comment was added'));
        // The anchor of the redirect does not reach the server, so the live list learns the
        // comment to scroll to from here.
        $this->session->flash(self::FLASH_ADDED, $commentId);

        return new RedirectResponse($this->pageUrl($object, $this->lastPage($object)) . '#comment-' . $commentId);
    }

    private function backToPage(CommentSubjectDTO $object, int $page): RedirectResponse
    {
        return new RedirectResponse($this->pageUrl($object, min(max(1, $page), $this->lastPage($object))) . '#' . self::ANCHOR);
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
        $this->navChain->add($object->heading ?: d__('system', 'Comments'), $linkDiscussion ? $object->discussionUrl : '');
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

    private function pageUrl(CommentSubjectDTO $object, int $page): string
    {
        return $page > 1 ? $object->discussionUrl . '?page=' . $page : $object->discussionUrl;
    }
}
