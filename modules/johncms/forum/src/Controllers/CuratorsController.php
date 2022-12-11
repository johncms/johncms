<?php

declare(strict_types=1);

namespace Johncms\Forum\Controllers;

use Johncms\Forum\Models\ForumMessage;
use Johncms\Forum\Models\ForumTopic;
use Johncms\Forum\Services\ForumTopicService;

class CuratorsController extends BaseForumController
{
    public function index(int $topicId, ForumTopicService $topicService): string
    {
        $topic = ForumTopic::query()->findOrFail($topicId);

        $availUsers = ForumMessage::query()
            ->select(['user_id', 'user_name'])
            ->where('topic_id', $topicId)
            ->whereDoesntHave('user.roles')
            ->groupBy('user_id', 'user_name')
            ->get();

        $total = $availUsers->count();

        $curators = [];
        $users = $topic->curators ?? [];

        if (isset($_POST['submit'])) {
            $users = $_POST['users'] ?? [];
            if (! is_array($users)) {
                $users = [];
            }
        }

        if ($total > 0) {
            foreach ($availUsers as $availUser) {
                $checked = array_key_exists($availUser->user_id, $users);
                if ($checked) {
                    $curators[$availUser->user_id] = $availUser->user_name;
                }
                $curators_list[] = [
                    'user_id'   => $availUser->user_id,
                    'user_name' => $availUser->user_name,
                    'checked'   => $checked,
                ];
            }

            if (isset($_POST['submit'])) {
                $topicService->update($topic, ['curators' => $curators]);
                $saved = true;
            }
        }

        return $this->render->render(
            'johncms/forum::curators',
            [
                'title'         => __('Curators'),
                'page_title'    => __('Curators'),
                'actionUrl'     => route('forum.curators', ['topicId' => $topicId]),
                'back_url'      => $topic->last_page_url,
                'total'         => $total,
                'curators_list' => $curators_list ?? [],
                'topic'         => $topic ?? [],
                'saved'         => $saved ?? false,
            ]
        );
    }
}
