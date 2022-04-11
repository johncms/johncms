<?php

declare(strict_types=1);

namespace Johncms\Forum\Messages;

use Illuminate\Database\Eloquent\Builder;
use Johncms\Forum\Models\ForumMessage;
use Johncms\Http\Session;

class ForumMessagesService
{
    protected Session $session;

    protected array $config;

    public function __construct(Session $session)
    {
        $this->session = $session;
        $this->config = [
            'farea'    => 0,
            'upfp'     => 0,
            'preview'  => 1,
            'postclip' => 1,
            'postcut'  => 2,
        ];
    }

    public function getTopicMessages(int $topicId)
    {
        $filterByUsers = $this->getFilterByUsers($topicId);

        return ForumMessage::query()
            ->with('files')
            ->where('topic_id', '=', $topicId)
            ->when(! empty($filterByUsers), function (Builder $builder) use ($filterByUsers) {
                return $builder->whereIn('user_id', $filterByUsers);
            })
            ->orderBy('id', ($this->config['upfp'] ? 'DESC' : 'ASC'))
            ->paginate();
    }

    protected function getFilterByUsers(int $topicId): array
    {
        $filter = $this->session->get('fsort_id', 0) === $topicId ? 1 : 0;
        $sortUsers = $this->session->get('fsort_users', '');
        if ($filter && ! empty($sortUsers)) {
            $filterByUsers = unserialize($sortUsers, ['allowed_classes' => false]);
        }
        return $filterByUsers ?? [];
    }
}
