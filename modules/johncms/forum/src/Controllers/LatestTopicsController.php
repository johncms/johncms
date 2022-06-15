<?php

declare(strict_types=1);

namespace Johncms\Forum\Controllers;

use Johncms\Forum\Models\ForumTopic;
use Johncms\Forum\Resources\UnreadTopicResource;
use Johncms\Http\Request;

class LatestTopicsController extends BaseForumController
{
    public function unread(): string
    {
        $this->metaTagManager->setAll(__('Unread'));
        $this->navChain->add(__('Unread'));
        $topics = ForumTopic::query()->unread()->orderByDesc('forum_topic.last_post_date')->paginate();
        $resource = UnreadTopicResource::createFromCollection($topics);

        return $this->render->render(
            'forum::new_topics',
            [
                'topics'        => $resource->getItems(),
                'pagination'    => $topics->render(),
                'empty_message' => __('The list is empty'),
                'total'         => $topics->total(),
                'show_period'   => false,
                'mark_as_read'  => '?act=new&amp;do=reset',
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
            'forum::new_topics',
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
}
