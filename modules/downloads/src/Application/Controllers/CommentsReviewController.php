<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Controllers;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\StaffTitles;
use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Downloads\Application\Services\DownloadFilePathService;
use Johncms\Modules\Downloads\Application\Services\DownloadsPermissions;
use Johncms\Modules\Downloads\Application\UseCases\ViewCommentsReviewUseCase;
use Johncms\NavChain;
use Johncms\Security\HtmlSanitizerInterface;
use Johncms\Smilies\SmiliesRendererInterface;
use Johncms\Utils\DateFormatterInterface;
use Symfony\Component\HttpFoundation\Response;
use Twig\Markup;

final readonly class CommentsReviewController
{
    public function __construct(
        private StaffTitles $staffTitles,
        private AccessCheckerInterface $accessChecker,
        private NavChain $navChain,
        private DateFormatterInterface $dateFormatter,
        private SmiliesRendererInterface $smiliesRenderer,
        private ViewCommentsReviewUseCase $useCase,
        private DownloadFilePathService $filePathService,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
        private HtmlSanitizerInterface $sanitizer,
    ) {
    }

    public function __invoke(): ViewResponse
    {
        $config = config('johncms');

        if (! $config['mod_down_comm'] && ! $this->accessChecker->allows(DownloadsPermissions::COMMENTS_ALWAYS_VIEW)) {
            return new ViewResponse(
                '@theme/pages/result.twig',
                [
                    'title'         => __('Comments are disabled'),
                    'type'          => 'alert-danger',
                    'message'       => __('Comments are disabled'),
                    'back_url'      => '/downloads/',
                    'back_url_name' => __('Downloads'),
                ],
                Response::HTTP_FORBIDDEN
            );
        }

        $pagination = $this->paginationFactory->create($this->useCase->count());

        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $result = $this->useCase->getPage($pagination->getPerPage(), $pagination->getOffset());

        $items = [];
        foreach ($result->comments as $comment) {
            $attrs = unserialize($comment->getAttribute('attributes'), ['allowed_classes' => false]);

            $text = $this->sanitizer->sanitize($comment->text);
            $text = $this->smiliesRenderer->render($text, $this->staffTitles->isStaff((int) $comment->user_id));

            $replyText = null;
            $replyTime = '';
            $replyAuthorUrl = '';
            $replyAuthorName = '';
            if (! empty($comment->reply)) {
                $reply = $this->sanitizer->sanitize($comment->reply);
                // reply_staff is what a reply records now; the older ones carry the number of
                // whoever wrote them.
                $replyIsOfStaff = (bool) ($attrs['reply_staff'] ?? (($attrs['reply_rights'] ?? 0) >= 1));
                $replyText = new Markup($this->smiliesRenderer->render($reply, $replyIsOfStaff), 'UTF-8');
                $replyTime = $this->dateFormatter->format($attrs['reply_time']);
                $replyAuthorUrl = '/profile/' . $attrs['reply_id'];
                $replyAuthorName = $attrs['reply_name'];
            }

            $items[] = [
                'user_id'                 => $comment->user_id,
                'name'                    => $attrs['author_name'],
                'created'                 => $this->dateFormatter->format($comment->time),
                'post_text'               => new Markup($text, 'UTF-8'),
                'reply_text'              => $replyText,
                'reply_time'              => $replyTime,
                'reply_author_url'        => $replyAuthorUrl,
                'reply_author_name'       => $replyAuthorName,
                'file_url'                => $this->filePathService->getFileUrlById((int) $comment->sub_id) ?? '/downloads/',
                'comments_url'            => '/downloads/comments/' . $comment->sub_id,
                'rus_name'                => $comment->rus_name ?? '',
                'search_ip_url'           => '/admin/ip-search?ip=' . long2ip((int) ($attrs['author_ip'] ?? 0)),
                'ip'                      => long2ip((int) ($attrs['author_ip'] ?? 0)),
                'search_ip_via_proxy_url' => '/admin/ip-search?ip=' . long2ip((int) ($attrs['author_ip_via_proxy'] ?? 0)),
                'ip_via_proxy'            => ! empty($attrs['author_ip_via_proxy']) ? long2ip((int) $attrs['author_ip_via_proxy']) : 0,
                'user_agent'              => $attrs['author_browser'] ?? '',
                'edit_count'              => $attrs['edit_count'] ?? 0,
                'editor_name'             => $attrs['edit_name'] ?? '',
                'edit_time'               => ! empty($attrs['edit_time']) ? $this->dateFormatter->format($attrs['edit_time']) : '',
                'edit_url'                => '',
                'delete_url'              => '',
            ];
        }

        $pageTitle = __('Review comments');
        $documentTitle = $pageTitle . ' — ' . __('Downloads');

        $this->navChain->add(__('Downloads'), '/downloads/');
        $this->navChain->add($pageTitle);

        $meta = new PageMeta($documentTitle, $pagination->getCurrentPage());

        return new ViewResponse(
            '@downloads/public/comments-review.twig',
            [
                'title'       => $meta->title,
                'page_title'  => $pageTitle,
                'description' => $meta->description,
                'items'       => $items,
                'pagination'  => $pagination->hasPages() ? $pagination->render() : null,
                'urls'        => ['downloads' => '/downloads/'],
            ]
        );
    }
}
