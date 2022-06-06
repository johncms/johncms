<?php

declare(strict_types=1);

namespace Johncms\Forum\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Johncms\Forum\Models\ForumMessage;
use Johncms\Http\Session;
use Johncms\Users\User;

class ForumMessagesService
{
    protected Session $session;
    protected ?User $user;

    protected array $config;

    public function __construct(Session $session, ?User $user)
    {
        $this->user = $user;
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

    public function delete(int | ForumMessage $message): void
    {
        if (is_int($message)) {
            $message = ForumMessage::query()->find($message);
        }

        DB::transaction(function () use ($message) {
            $files = $message->files;
            foreach ($files as $file) {
                unlink(UPLOAD_PATH . 'forum/attach/' . $file->filename);
                $file->delete();
            }
            $message->delete();
        });
    }

    public function hide(int | ForumMessage $message): void
    {
        if (is_int($message)) {
            $message = ForumMessage::query()->find($message);
        }

        $countMessages = ForumMessage::query()->where('topic_id', $message->topic_id)->count();
        DB::transaction(function () use ($message, $countMessages) {
            $files = $message->files;
            foreach ($files as $file) {
                $file->update(['del' => 1]);
            }
            if ($countMessages === 1) {
                // If the last post of the topic then hide topic
                $topic = $message->topic;
                $topicService = di(ForumTopicService::class);
                $topicService->hide($topic);
            } else {
                // else hide the post
                $message->update(['deleted' => true, 'deleted_by' => $this->user?->display_name]);
            }
        });
    }
}
