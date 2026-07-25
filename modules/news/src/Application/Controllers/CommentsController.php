<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Controllers;

use Carbon\Carbon;
use Exception;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Johncms\FileInfo;
use Johncms\Files\FileStorage;
use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Media\MediaEmbed;
use Johncms\Modules\News\Application\Utils\Helpers;
use Johncms\Modules\News\Domain\Models\NewsArticle;
use Johncms\Modules\News\Domain\Models\NewsComments;
use Johncms\Security\HTMLPurifier;
use Johncms\Smilies\SmiliesRendererInterface;
use Johncms\Http\Environment;
use Johncms\Http\Request;
use Johncms\System\View\Extension\Avatar;
use Johncms\Users\User;
use League\Flysystem\FilesystemException;

final readonly class CommentsController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private PaginationFactory $paginationFactory,
    ) {
        $this->controllerContext->initModule('news');
    }

    /**
     * The list of comments
     *
     * @param int $article_id
     * @param Avatar $avatar
     * @param SmiliesRendererInterface $smiliesRenderer
     * @param User $current_user
     */
    public function index(int $article_id, Avatar $avatar, SmiliesRendererInterface $smiliesRenderer, User $current_user): void
    {
        if ($article_id === 0) {
            http_response_code(400);
            Helpers::returnJson(['error' => __('Bad Request')]);
        }

        $pagination = $this->paginationFactory->create(
            (new NewsComments())->where('article_id', $article_id)->count()
        );

        $comments = (new NewsComments())
            ->with('user')
            ->where('article_id', $article_id)
            ->offset($pagination->getOffset())
            ->limit($pagination->getPerPage())
            ->get();

        $purifier = di(HTMLPurifier::class);
        $embed = di(MediaEmbed::class);

        $total = $pagination->getTotal();
        $currentPage = $pagination->getCurrentPage();
        $lastPage = $pagination->getTotalPages();

        $array = [
            'current_page'   => $currentPage,
            'data'           => $comments->map(
                static function (NewsComments $comment) use ($avatar, $smiliesRenderer, $current_user, $purifier, $embed) {
                    $user = $comment->user;
                    $user_data = [];
                    if ($user) {
                        $user_data = [
                            'id'          => $user->id,
                            'user_name'   => $user->name,
                            'status'      => $user->status,
                            'is_online'   => $user->is_online,
                            'rights_name' => $user->rights_name,
                            'profile_url' => $user->profile_url,
                            'avatar'      => $avatar->getUserAvatar($user->id),
                        ];
                    }

                    $text = $purifier->purify($comment->text);
                    $text = $embed->embedMedia($text);
                    $text = $smiliesRenderer->render($text, ($user->rights > 0));

                    $message = [
                        'id'         => $comment->id,
                        'created_at' => $comment->created_at,
                        'text'       => $text,
                        'user'       => $user_data,
                    ];

                    if ($current_user->id === $user->id) {
                        $message['can_delete'] = true;
                    }

                    $message['can_quote'] = false;
                    $message['can_reply'] = false;
                    if ($current_user->id !== $user->id && $current_user->isValid()) {
                        $message['can_quote'] = true;
                        $message['can_reply'] = true;
                    }

                    if ($current_user->rights > 6) {
                        $message['can_delete'] = true;
                        $message['user_agent'] = Arr::get($comment->user_data, 'user_agent', '');
                        $message['ip'] = Arr::get($comment->user_data, 'ip', '');
                        $message['search_ip_url'] = '/admin/ip-search?ip=' . $message['ip'];
                        $message['ip_via_proxy'] = Arr::get($comment->user_data, 'ip_via_proxy', '');
                        $message['search_ip_via_proxy_url'] = '/admin/ip-search?ip=' . $message['ip_via_proxy'];
                    }

                    return $message;
                }
            ),
            'from'           => $total > 0 ? $pagination->getOffset() + 1 : null,
            'last_page'      => $lastPage,
            'next_page_url'  => $currentPage < $lastPage ? $pagination->getUrl($currentPage + 1) : null,
            'per_page'       => $pagination->getPerPage(),
            'prev_page_url'  => $currentPage > 1 ? $pagination->getUrl($currentPage - 1) : null,
            'to'             => $total > 0 ? $pagination->getOffset() + $comments->count() : null,
            'total'          => $total,
        ];

        Helpers::returnJson($array);
    }

    public function add(int $article_id, Request $request, User $user, Environment $env): void
    {
        $post_body = $this->decodeJsonBody($request);

        if (! empty($user->ban)) {
            http_response_code(403);
            Helpers::returnJson(['message' => __('You have a ban!')]);
        }

        if (! $user->isValid()) {
            http_response_code(403);
            Helpers::returnJson(['message' => __('You are not logged in')]);
        }

        try {
            $article = (new NewsArticle())->findOrFail($article_id);
            $comment = trim((string) ($post_body['comment'] ?? ''));
            if (! empty($comment)) {
                $attached_files = array_map('intval', (array) ($post_body['attached_files'] ?? []));
                (new NewsComments())->create(
                    [
                        'article_id'     => $article->id,
                        'user_id'        => $user->id,
                        'text'           => $comment,
                        'user_data'      => [
                            'user_agent'   => $env->getUserAgent(),
                            'ip'           => $env->getIp(false),
                            'ip_via_proxy' => $env->getIpViaProxy(false),
                        ],
                        'created_at'     => Carbon::now()->format('Y-m-d H:i:s'),
                        'attached_files' => $attached_files,
                    ]
                );

                $last_page = $this->paginationFactory->create(
                    (new NewsComments())->where('article_id', $article->id)->count(),
                    $user->config->kmess
                )->getTotalPages();
                Helpers::returnJson(['message' => __('The comment was added successfully'), 'last_page' => $last_page]);
            } else {
                http_response_code(422);
                Helpers::returnJson(['message' => __('Enter the comment text')]);
            }
        } catch (ModelNotFoundException $exception) {
            Helpers::returnJson(['message' => $exception->getMessage()]);
        }
    }

    public function del(Request $request, User $user, FileStorage $storage): void
    {
        $post_body = $this->decodeJsonBody($request);

        $comment_id = $post_body['comment_id'] ?? 0;

        try {
            $post = (new NewsComments())->findOrFail($comment_id);
            if ($user->rights >= 6 || $user->id === $post->user_id) {
                try {
                    if (! empty($post->attached_files)) {
                        foreach ($post->attached_files as $attached_file) {
                            try {
                                $storage->delete($attached_file);
                            } catch (Exception | FilesystemException $exception) {
                            }
                        }
                    }
                    $post->forceDelete();
                    Helpers::returnJson(['message' => __('The comment was deleted successfully')]);
                } catch (\Exception $e) {
                    http_response_code(500);
                    Helpers::returnJson(['message' => $e->getMessage()]);
                }
            } else {
                http_response_code(403);
                Helpers::returnJson(['message' => __('Access denied')]);
            }
        } catch (ModelNotFoundException $exception) {
            http_response_code(404);
            Helpers::returnJson(['message' => $exception->getMessage()]);
        }
    }

    public function loadFile(Request $request): string
    {
        try {
            /** @var UploadedFile[] $files */
            $files = $request->files->all();
            $file_info = new FileInfo($files['upload']->getClientOriginalName());
            if (! $file_info->isImage()) {
                return json_encode(
                    [
                        'error' => [
                            'message' => __('Only images are allowed'),
                        ],
                    ]
                );
            }

            $file = (new FileStorage())->saveFromRequest('upload', 'news_comments');
            $file_array = [
                'id'       => $file->id,
                'name'     => $file->name,
                'uploaded' => 1,
                'url'      => $file->url,
            ];
            header('Content-Type: application/json');
            return json_encode($file_array);
        } catch (FilesystemException | Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            return json_encode(['errors' => $e->getMessage()]);
        }
    }

    /**
     * Decodes the JSON body these endpoints are called with.
     *
     * @return array<string, mixed>
     */
    private function decodeJsonBody(Request $request): array
    {
        $decoded = json_decode($request->getContent(), true);

        return is_array($decoded) ? $decoded : [];
    }
}
