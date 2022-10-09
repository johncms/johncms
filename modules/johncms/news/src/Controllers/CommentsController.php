<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\News\Controllers;

use Carbon\Carbon;
use Exception;
use GuzzleHttp\Psr7\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Johncms\Controller\BaseController;
use Johncms\Exceptions\PageNotFoundException;
use Johncms\Files\FileInfo;
use Johncms\Files\FileStorage;
use Johncms\Http\Request;
use Johncms\Http\Response\JsonResponse;
use Johncms\Media\MediaEmbed;
use Johncms\News\Models\NewsArticle;
use Johncms\News\Models\NewsComments;
use Johncms\Security\HTMLPurifier;
use Johncms\System\Legacy\Tools;
use Johncms\Users\User;
use Johncms\View\Extension\Avatar;
use League\Flysystem\FilesystemException;
use Psr\Http\Message\ResponseInterface;

class CommentsController extends BaseController
{
    protected string $moduleName = 'johncms/news';

    /**
     * The list of comments
     *
     * @param int $article_id
     * @param Avatar $avatar
     * @param Tools $tools
     * @param User|null $current_user
     * @return array
     */
    public function index(int $article_id, Avatar $avatar, Tools $tools, ?User $current_user = null): array
    {
        if ($article_id === 0) {
            throw new PageNotFoundException();
        }

        /** @var LengthAwarePaginator $comments */
        $comments = (new NewsComments())->with('user')->where('article_id', $article_id)->paginate();

        $purifier = di(HTMLPurifier::class);
        $embed = di(MediaEmbed::class);

        return [
            'current_page'   => $comments->currentPage(),
            'data'           => $comments->getItems()->map(
                static function (NewsComments $comment) use ($avatar, $tools, $current_user, $purifier, $embed) {
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
                    $text = $tools->smilies($text, ($user->rights > 0));

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
                    if ($current_user?->id !== $user->id) {
                        $message['can_quote'] = true;
                        $message['can_reply'] = true;
                    }

                    if ($current_user->rights > 6) {
                        $message['can_delete'] = true;
                        $message['user_agent'] = Arr::get($comment->user_data, 'user_agent', '');
                        $message['ip'] = Arr::get($comment->user_data, 'ip', '');
                        $message['search_ip_url'] = '/admin/search_ip/?ip=' . $message['ip'];
                        $message['ip_via_proxy'] = Arr::get($comment->user_data, 'ip_via_proxy', '');
                        $message['search_ip_via_proxy_url'] = '/admin/search_ip/?ip=' . $message['ip_via_proxy'];
                    }

                    return $message;
                }
            ),
            'first_page_url' => $comments->url(1),
            'from'           => $comments->firstItem(),
            'last_page'      => $comments->lastPage(),
            'last_page_url'  => $comments->url($comments->lastPage()),
            'next_page_url'  => $comments->nextPageUrl(),
            'path'           => $comments->path(),
            'per_page'       => $comments->perPage(),
            'prev_page_url'  => $comments->previousPageUrl(),
            'to'             => $comments->lastItem(),
            'total'          => $comments->total(),
        ];
    }

    public function add(int $article_id, Request $request, ?User $user = null): ResponseInterface
    {
        if (! empty($user?->ban)) {
            return new JsonResponse(['message' => __('You have a ban!')], 403);
        }

        if (! $user) {
            return new JsonResponse(['message' => __('You are not logged in')], 403);
        }

        $article = (new NewsArticle())->findOrFail($article_id);
        $comment = $request->getJson('comment');
        if (! empty($comment)) {
            $attached_files = array_map('intval', (array) $request->getJson('attached_files', []));
            (new NewsComments())->create(
                [
                    'article_id'     => $article->id,
                    'user_id'        => $user->id,
                    'text'           => $comment,
                    'user_data'      => [
                        'user_agent'   => $request->getUserAgent(),
                        'ip'           => $request->getIp(),
                        'ip_via_proxy' => $request->getIpViaProxy(),
                    ],
                    'created_at'     => Carbon::now()->format('Y-m-d H:i:s'),
                    'attached_files' => $attached_files,
                ]
            );

            $last_page = (new NewsComments())->where('article_id', $article->id)->paginate($user->settings->perPage)->lastPage();
            return new JsonResponse(['message' => __('The comment was added successfully'), 'last_page' => $last_page]);
        } else {
            return new JsonResponse(['message' => __('Enter the comment text')], 422);
        }
    }

    public function del(Request $request, User $user, FileStorage $storage): array|JsonResponse
    {
        $comment_id = $request->getJson('comment_id', 0, FILTER_VALIDATE_INT);

        $post = (new NewsComments())->findOrFail($comment_id);
        // TODO: Replace to check permission
        if ($user->hasRole('admin') || $user->id === $post->user_id) {
            try {
                if (! empty($post->attached_files)) {
                    foreach ($post->attached_files as $attached_file) {
                        try {
                            $storage->delete($attached_file);
                        } catch (Exception | FilesystemException) {
                        }
                    }
                }
                $post->forceDelete();
                return ['message' => __('The comment was deleted successfully')];
            } catch (Exception $e) {
                return new JsonResponse(['message' => $e->getMessage()], 500);
            }
        }
        return new JsonResponse(['message' => __('Access denied')], 403);
    }

    public function loadFile(Request $request): array|JsonResponse
    {
        try {
            /** @var UploadedFile[] $files */
            $files = $request->getUploadedFiles();
            $file_info = new FileInfo($files['upload']->getClientFilename());
            if (! $file_info->isImage()) {
                return new JsonResponse(
                    [
                        'error' => [
                            'message' => __('Only images are allowed'),
                        ],
                    ],
                    500
                );
            }

            $file = (new FileStorage())->saveFromRequest('upload', 'news_comments');
            return [
                'id'       => $file->id,
                'name'     => $file->name,
                'uploaded' => 1,
                'url'      => $file->url,
            ];
        } catch (FilesystemException | Exception $e) {
            return new JsonResponse(['errors' => $e->getMessage()], 500);
        }
    }
}
