<?php

declare(strict_types=1);

namespace News\Actions;

use News\Models\NewsArticle;
use News\Models\NewsComments;
use News\Utils\AbstractAction;
use News\Utils\Helpers;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Johncms\System\Http\Environment;
use Johncms\System\Legacy\Tools;
use Johncms\System\View\Extension\Avatar;

class Comments extends AbstractAction
{
    /**
     * The list of comments
     */
    public function index(): void
    {
        $article_id = $this->request->getQuery('article_id', 0, FILTER_VALIDATE_INT);
        if (empty($article_id)) {
            http_response_code(400);
            Helpers::returnJson(['error' => __('Bad Request')]);
        }

        if (! empty($article_id)) {
            /** @var LengthAwarePaginator $comments */
            $comments = (new NewsComments())->with('user')->where('article_id', $article_id)->paginate($this->user->config->kmess);

            /** @var Avatar $avatar */
            $avatar = di(Avatar::class);

            /** @var Tools $tools */
            $tools = di(Tools::class);

            $current_user = $this->user;

            $array = [
                'current_page'   => $comments->currentPage(),
                'data'           => $comments->getItems()->map(
                    static function (NewsComments $comment) use ($avatar, $tools, $current_user) {
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

                        $text = $tools->checkout($comment->text, 1, 1);
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
                        if ($current_user->id !== $user->id && $current_user->isValid()) {
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

            Helpers::returnJson($array);
        }
    }

    public function add(): void
    {
        $article_id = $this->request->getQuery('article_id', 0);
        $post_body = $this->request->getBody();
        if ($post_body) {
            $post_body = json_decode($post_body->getContents(), true);
        }

        if (! empty($this->user->ban)) {
            http_response_code(403);
            Helpers::returnJson(['message' => __('You have a ban!')]);
        }

        if (! $this->user->isValid()) {
            http_response_code(403);
            Helpers::returnJson(['message' => __('You are not logged in')]);
        }

        try {
            $article = (new NewsArticle())->findOrFail($article_id);
        } catch (ModelNotFoundException $exception) {
            Helpers::returnJson(['message' => $exception->getMessage()]);
        }

        /** @var Environment $env */
        $env = di(Environment::class);

        $comment = trim($post_body['comment']);

        if (! empty($comment)) {
            (new NewsComments())->create(
                [
                    'article_id' => $article->id,
                    'user_id'    => $this->user->id,
                    'text'       => $comment,
                    'user_data'  => [
                        'user_agent'   => $env->getUserAgent(),
                        'ip'           => $env->getIp(false),
                        'ip_via_proxy' => $env->getIpViaProxy(false),
                    ],
                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                ]
            );

            $last_page = (new NewsComments())->where('article_id', $article->id)->paginate($this->user->config->kmess)->lastPage();
            Helpers::returnJson(['message' => __('The comment was added successfully'), 'last_page' => $last_page]);
        } else {
            http_response_code(422);
            Helpers::returnJson(['message' => __('Enter the comment text')]);
        }
    }

    public function del(): void
    {
        $post_body = $this->request->getBody();
        if ($post_body) {
            $post_body = json_decode($post_body->getContents(), true);
        }

        $comment_id = $post_body['comment_id'] ?? 0;

        try {
            $post = (new NewsComments())->findOrFail($comment_id);
        } catch (ModelNotFoundException $exception) {
            http_response_code(404);
            Helpers::returnJson(['message' => $exception->getMessage()]);
        }

        if ($this->user->rights >= 6 || $this->user->id === $post->user_id) {
            try {
                $post->delete();
                Helpers::returnJson(['message' => __('The comment was deleted successfully')]);
            } catch (\Exception $e) {
                http_response_code(500);
                Helpers::returnJson(['message' => $e->getMessage()]);
            }
        } else {
            http_response_code(403);
            Helpers::returnJson(['message' => __('Access denied')]);
        }
    }
}
