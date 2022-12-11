<?php

declare(strict_types=1);

namespace Johncms\Forum\Controllers;

use Illuminate\Support\Facades\DB;
use Johncms\Forum\Models\ForumTopic;
use Johncms\Forum\Resources\UnreadTopicResource;
use Johncms\Http\Request;
use Johncms\Users\User;

class LatestTopicsController extends BaseForumController
{
    public function unread(): string
    {
        $this->metaTagManager->setAll(__('Unread'));
        $this->navChain->add(__('Unread'));
        $topics = ForumTopic::query()->unread()->orderByDesc('forum_topic.last_post_date')->paginate();
        $resource = UnreadTopicResource::createFromCollection($topics);

        return $this->render->render(
            'johncms/forum::new_topics',
            [
                'topics'        => $resource->getItems(),
                'pagination'    => $topics->render(),
                'empty_message' => __('The list is empty'),
                'total'         => $topics->total(),
                'show_period'   => false,
                'mark_as_read'  => route('forum.markAsRead'),
            ]
        );
    }

    public function period(Request $request): string
    {
        $period = $request->getQuery('period', 24, FILTER_VALIDATE_INT);
        $this->metaTagManager->setAll(__('All for period %d hours', $period));
        $this->navChain->add(__('Show for Period'));

        $topics = ForumTopic::query()->period(time() - $period * 3600)->orderByDesc('forum_topic.last_post_date')->paginate();
        $resource = UnreadTopicResource::createFromCollection($topics);

        return $this->render->render(
            'johncms/forum::new_topics',
            [
                'current_period' => $period,
                'topics'         => $resource->getItems(),
                'pagination'     => $topics->render(),
                'empty_message'  => __('The list is empty'),
                'total'          => $topics->total(),
                'show_period'    => true,
            ]
        );
    }

    public function markAsRead(User $user): string
    {
        $unread = DB::select(
            "SELECT `forum_topic`.`id`, `forum_topic`.`last_post_date`
            FROM `forum_topic` LEFT JOIN `forum_read` ON `forum_topic`.`id` = `forum_read`.`topic_id` AND `forum_read`.`user_id` = '" . $user->id . "'
            WHERE `forum_topic`.`last_post_date` > `forum_read`.`time` OR `forum_read`.`topic_id` IS NULL"
        );

        $values = [];
        foreach ($unread as $item) {
            $values[] = '(' . $item->id . ', ' . $user->id . ', ' . $item->last_post_date . ')';
        }

        if (! empty($values)) {
            DB::statement(
                'INSERT INTO forum_read (topic_id, user_id, `time`) VALUES ' . implode(',', $values) . '
                    ON DUPLICATE KEY UPDATE `time` = VALUES(`time`)'
            );
        }

        return $this->render->render(
            'johncms/system::pages/result',
            [
                'title'         => __('Unread'),
                'type'          => 'alert-success',
                'message'       => __('All topics marked as read'),
                'back_url'      => '/forum/',
                'back_url_name' => __('Forum'),
            ]
        );
    }

    public function latest(): string
    {
        $this->metaTagManager->setAll(__('Last Activity'));
        $this->navChain->add(__('Last Activity'));
        $topics = ForumTopic::query()->forLatest()->orderByDesc('forum_topic.last_post_date')->paginate();
        $resource = UnreadTopicResource::createFromCollection($topics);

        return $this->render->render(
            'johncms/forum::new_topics',
            [
                'topics'        => $resource->getItems(),
                'pagination'    => $topics->render(),
                'empty_message' => __('The list is empty'),
                'total'         => $topics->total(),
                'show_period'   => false,
                'mark_as_read'  => null,
            ]
        );
    }
}
