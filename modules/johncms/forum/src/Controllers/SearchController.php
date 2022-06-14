<?php

declare(strict_types=1);

namespace Johncms\Forum\Controllers;

use Johncms\Forum\ForumUtils;
use Johncms\Forum\Models\ForumMessage;
use Johncms\Forum\Models\ForumTopic;
use Johncms\Http\Request;
use Johncms\System\Legacy\Tools;
use Johncms\Users\User;

class SearchController extends BaseForumController
{
    public function index(Tools $tools): string
    {
        $this->metaTagManager->setAll(__('Forum search'));

        // Принимаем данные, выводим форму поиска
        $search_post = isset($_POST['search']) ? trim($_POST['search']) : '';
        $search_get = isset($_GET['search']) ? rawurldecode(trim($_GET['search'])) : '';
        $search = ! empty($search_post) ? $search_post : $search_get;
        $search = preg_replace("/[^\w\x7F-\xFF\s]/", ' ', $search);
        $search_t = isset($_REQUEST['t']);


        $error = (($search && mb_strlen($search) < 4) || mb_strlen($search) > 64);
        $results = [];
        $total = 0;
        if ($search && ! $error) {
            // Выводим результаты запроса
            $array = explode(' ', $search);
            if ($search_t) {
                $messages = ForumTopic::query()->where('name', 'like', '%' . $search . '%')->paginate();
            } else {
                $messages = ForumMessage::query()->with('topic')->whereFullText('text', $search)->paginate();
            }
            $total = $messages->total();

            if ($total) {
                foreach ($messages as $message) {
                    if ($search_t) {
                        $post = [
                            'id'             => $message->id,
                            'name'           => $message->name,
                            'formatted_date' => $message->show_last_post_date,
                            'read_more'      => $message->last_page_url,
                            'topic_url'      => $message->last_page_url,
                            'user_id'        => $message->user_id,
                            'user_name'      => $message->user_name,
                        ];
                    } else {
                        $post = [
                            'id'             => $message->id,
                            'name'           => $message->topic->name,
                            'formatted_date' => format_date($message->date),
                            'read_more'      => $message->url,
                            'post_url'       => $message->url,
                            'topic_url'      => $message->topic->last_page_url,
                            'user_id'        => $message->user_id,
                            'user_name'      => $message->user_name,
                        ];
                    }

                    $text = $search_t ? $message->name : $message->text;

                    foreach ($array as $srch) {
                        $needle = strtolower(str_replace('*', '', $srch));
                        $pos = (! empty($message->text) && ! empty($needle)) ? mb_stripos($message->text, $needle) : false;
                        if ($pos !== false) {
                            break;
                        }
                    }
                    if (! isset($pos) || $pos < 100) {
                        $pos = 100;
                    }
                    $text = preg_replace('#\[c\](.*?)\[/c\]#si', '<div class="quote">\1</div>', $text);
                    $text = $tools->checkout(mb_substr($text, ($pos - 100), 400), 1);
                    if (! $search_t) {
                        foreach ($array as $val) {
                            $text = ForumUtils::replaceKeywords($val, $text);
                        }
                    }

                    $post['formatted_text'] = $text;

                    $results[] = $post;
                }
            }
        } elseif ($error) {
            return $this->render->render(
                'system::pages/result',
                [
                    'type'          => 'alert-danger',
                    'message'       => __('Invalid length'),
                    'back_url'      => '/forum/?act=search',
                    'back_url_name' => __('Repeat'),
                ]
            );
        }

        return $this->render->render(
            'forum::forum_search',
            [
                'pagination' => isset($messages) ? $messages->render() : '',
                'query'      => htmlspecialchars($search),
                'search_t'   => $search_t,
                'results'    => $results,
                'total'      => $total,
            ]
        );
    }
}
