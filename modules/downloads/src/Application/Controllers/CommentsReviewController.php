<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\PageMeta;
use Johncms\Modules\Downloads\Application\Services\DownloadFilePathService;
use Johncms\Modules\Downloads\Application\UseCases\ViewCommentsReviewUseCase;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\Legacy\Tools;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class CommentsReviewController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private Tools $tools,
        private User $currentUser,
        private ViewCommentsReviewUseCase $useCase,
        private DownloadFilePathService $filePathService,
    ) {
        $this->controllerContext->initModule('downloads');
    }

    public function __invoke(): string
    {
        $config = config('johncms');

        if (! $config['mod_down_comm'] && $this->currentUser->rights < 7) {
            http_response_code(403);
            return $this->render->render(
                'system::pages/result',
                [
                    'title'         => __('Comments are disabled'),
                    'type'          => 'alert-danger',
                    'message'       => __('Comments are disabled'),
                    'back_url'      => '/downloads/',
                    'back_url_name' => __('Downloads'),
                ]
            );
        }

        $page = max(1, (int) $this->request->getQuery('page', 1));

        $result = $this->useCase->execute($page, $this->currentUser->config->kmess);

        $items = [];
        foreach ($result->comments as $comment) {
            $attrs = unserialize($comment->getAttribute('attributes'), ['allowed_classes' => false]);

            $text = $this->tools->checkout($comment->text, 1, 1);
            $text = $this->tools->smilies($text, ($comment->user_rights ?? 0) >= 1 ? 1 : 0);

            $replyText = '';
            $replyTime = '';
            $replyAuthorUrl = '';
            $replyAuthorName = '';
            if (! empty($comment->reply)) {
                $reply = $this->tools->checkout($comment->reply, 1, 1);
                $replyText = $this->tools->smilies($reply, ($attrs['reply_rights'] ?? 0) >= 1 ? 1 : 0);
                $replyTime = $this->tools->displayDate($attrs['reply_time']);
                $replyAuthorUrl = '/profile/' . $attrs['reply_id'];
                $replyAuthorName = $attrs['reply_name'];
            }

            $items[] = [
                'user_id'                 => $comment->user_id,
                'name'                    => $attrs['author_name'],
                'created'                 => $this->tools->displayDate($comment->time),
                'post_text'               => $text,
                'reply_text'              => $replyText,
                'reply_time'              => $replyTime,
                'reply_author_url'        => $replyAuthorUrl,
                'reply_author_name'       => $replyAuthorName,
                'file_url'                => $this->filePathService->getFileUrlById((int) $comment->sub_id) ?? '/downloads/',
                'comments_url'            => '/downloads/comments/' . $comment->sub_id,
                'rus_name'                => htmlspecialchars($comment->rus_name ?? ''),
                'search_ip_url'           => '/admin/search_ip/?ip=' . long2ip((int) ($attrs['author_ip'] ?? 0)),
                'ip'                      => long2ip((int) ($attrs['author_ip'] ?? 0)),
                'search_ip_via_proxy_url' => '/admin/search_ip/?ip=' . long2ip((int) ($attrs['author_ip_via_proxy'] ?? 0)),
                'ip_via_proxy'            => ! empty($attrs['author_ip_via_proxy']) ? long2ip((int) $attrs['author_ip_via_proxy']) : 0,
                'user_agent'              => $attrs['author_browser'] ?? '',
                'edit_count'              => $attrs['edit_count'] ?? 0,
                'editor_name'             => $attrs['edit_name'] ?? '',
                'edit_time'               => ! empty($attrs['edit_time']) ? $this->tools->displayDate($attrs['edit_time']) : '',
                'edit_url'                => '',
                'delete_url'              => '',
            ];
        }

        $pageTitle = __('Review comments');
        $documentTitle = $pageTitle . ' — ' . __('Downloads');

        $this->navChain->add(__('Downloads'), '/downloads/');
        $this->navChain->add($pageTitle);

        $meta = new PageMeta($documentTitle, $page);
        $this->render->addData([
            'title'       => $meta->title,
            'page_title'  => $pageTitle,
            'description' => $meta->description,
        ]);

        $total = $result->comments->total();

        return $this->render->render(
            'downloads::comments_review',
            [
                'data' => [
                    'items'      => $items,
                    'pagination' => $total > $this->currentUser->config->kmess
                        ? $this->tools->displayPagination(
                            '/downloads/comments-review/?',
                            ($page - 1) * $this->currentUser->config->kmess,
                            $total,
                            $this->currentUser->config->kmess
                        )
                        : '',
                ],
                'urls' => ['downloads' => '/downloads/'],
            ]
        );
    }
}
