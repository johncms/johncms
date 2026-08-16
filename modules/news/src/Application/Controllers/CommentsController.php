<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Controllers;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\StaffTitles;
use Johncms\Auth\CurrentUser;
use Johncms\FileInfo;
use Johncms\Files\FileStorage;
use Johncms\Http\Environment;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Request;
use Johncms\Media\MediaEmbed;
use Johncms\Modules\News\Application\Services\NewsPermissions;
use Johncms\Modules\News\Domain\Models\NewsArticle;
use Johncms\Modules\News\Domain\Models\NewsComments;
use Johncms\Security\HtmlSanitizerInterface;
use Johncms\Smilies\SmiliesRendererInterface;
use Johncms\Users\User;
use Johncms\View\Twig\Runtime\AssetRuntime;
use League\Flysystem\FilesystemException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class CommentsController
{
    public function __construct(
        private StaffTitles $staffTitles,
        private AccessCheckerInterface $accessChecker,
        private PaginationFactory $paginationFactory,
    ) {
    }

    /**
     * The list of comments
     *
     * @param int $article_id
     * @param AssetRuntime $assets
     * @param SmiliesRendererInterface $smiliesRenderer
     */
    public function index(int $article_id, AssetRuntime $assets, SmiliesRendererInterface $smiliesRenderer, CurrentUser $current_user): Response
    {
        if ($article_id === 0) {
            return new JsonResponse(['error' => __('Bad Request')], Response::HTTP_BAD_REQUEST);
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

        $sanitizer = di(HtmlSanitizerInterface::class);
        $embed = di(MediaEmbed::class);

        $total = $pagination->getTotal();
        $currentPage = $pagination->getCurrentPage();
        $lastPage = $pagination->getTotalPages();

        $canModerate = $this->accessChecker->allows(NewsPermissions::COMMENTS_MODERATE);
        $staffTitles = $this->staffTitles;

        $array = [
            'current_page'   => $currentPage,
            'data'           => $comments->map(
                static function (NewsComments $comment) use ($assets, $smiliesRenderer, $current_user, $sanitizer, $embed, $canModerate, $staffTitles) {
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
                            'avatar'      => $assets->avatar((int) $user->id),
                        ];
                    }

                    $text = $sanitizer->sanitize($comment->text);
                    $text = $embed->embedMedia($text);
                    $text = $smiliesRenderer->render($text, $staffTitles->isStaff((int) $comment->getAttribute('user_id')));

                    $message = [
                        'id'         => $comment->id,
                        'created_at' => $comment->created_at,
                        'text'       => $text,
                        'user'       => $user_data,
                    ];

                    if ($current_user->id() === $user->id) {
                        $message['can_delete'] = true;
                    }

                    $message['can_quote'] = false;
                    $message['can_reply'] = false;
                    if ($current_user->id() !== $user->id && $current_user->isValid()) {
                        $message['can_quote'] = true;
                        $message['can_reply'] = true;
                    }

                    if ($canModerate) {
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

        return new JsonResponse($array);
    }

    public function add(int $article_id, Request $request, CurrentUser $currentUser, Environment $env): Response
    {
        $post_body = $this->decodeJsonBody($request);

        if (! empty($currentUser->user()->ban)) {
            return new JsonResponse(['message' => __('You have a ban!')], Response::HTTP_FORBIDDEN);
        }

        if (! $currentUser->isValid()) {
            return new JsonResponse(['message' => __('You are not logged in')], Response::HTTP_FORBIDDEN);
        }

        try {
            $article = (new NewsArticle())->findOrFail($article_id);
            $comment = trim((string) ($post_body['comment'] ?? ''));
            if (! empty($comment)) {
                $attached_files = array_map('intval', (array) ($post_body['attached_files'] ?? []));
                (new NewsComments())->create(
                    [
                        'article_id'     => $article->id,
                        'user_id'        => $currentUser->id(),
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
                    $currentUser->user()->config->kmess
                )->getTotalPages();
                return new JsonResponse(['message' => __('The comment was added successfully'), 'last_page' => $last_page]);
            }

            return new JsonResponse(['message' => __('Enter the comment text')], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (ModelNotFoundException $exception) {
            return new JsonResponse(['message' => $exception->getMessage()]);
        }
    }

    public function del(Request $request, CurrentUser $currentUser, FileStorage $storage): Response
    {
        $post_body = $this->decodeJsonBody($request);

        $comment_id = $post_body['comment_id'] ?? 0;

        try {
            $post = (new NewsComments())->findOrFail($comment_id);
            if ($currentUser->id() === $post->user_id || $this->accessChecker->allows(NewsPermissions::COMMENTS_MODERATE)) {
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
                    return new JsonResponse(['message' => __('The comment was deleted successfully')]);
                } catch (\Exception $e) {
                    return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            }

            return new JsonResponse(['message' => __('Access denied')], Response::HTTP_FORBIDDEN);
        } catch (ModelNotFoundException $exception) {
            return new JsonResponse(['message' => $exception->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    public function loadFile(Request $request): JsonResponse
    {
        try {
            /** @var UploadedFile[] $files */
            $files = $request->files->all();
            $file_info = new FileInfo($files['upload']->getClientOriginalName());
            if (! $file_info->isImage()) {
                return new JsonResponse(
                    [
                        'error' => [
                            'message' => __('Only images are allowed'),
                        ],
                    ]
                );
            }

            $file = (new FileStorage())->saveFromRequest($request, 'upload', 'news_comments');
            $file_array = [
                'id'       => $file->id,
                'name'     => $file->name,
                'uploaded' => 1,
                'url'      => $file->url,
            ];
            return new JsonResponse($file_array);
        } catch (FilesystemException | Exception $e) {
            return new JsonResponse(['errors' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
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
