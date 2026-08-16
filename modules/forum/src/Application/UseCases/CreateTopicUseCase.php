<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Carbon\Carbon;
use Johncms\Auth\CurrentUser;
use Johncms\Modules\Forum\Application\DTO\NewTopicResultDTO;
use Johncms\Modules\Forum\Application\Services\ForumTopicSlugService;
use Johncms\Modules\Forum\Application\Services\ForumTopicStatsRecalculator;
use Johncms\Modules\Forum\Domain\Models\ForumMessage;
use Johncms\Modules\Forum\Domain\Models\ForumSection;
use Johncms\Modules\Forum\Domain\Models\ForumTopic;
use Johncms\Modules\Forum\Domain\Models\ForumUnread;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;
use Johncms\Security\ClientInfoDTO;

final readonly class CreateTopicUseCase
{
    public function __construct(
        private ForumTopicRepositoryInterface $topicRepository,
        private ForumTopicStatsRecalculator $topicStatsRecalculator,
        private ForumTopicSlugService $topicSlugService,
        private CurrentUser $currentUser,
    ) {
    }

    public function execute(
        ForumSection $section,
        string $topicName,
        string $messageText,
        ?string $metaKeywords,
        ?string $metaDescription,
        ClientInfoDTO $clientInfo,
    ): NewTopicResultDTO {
        $topic = new ForumTopic();
        $topic->section_id = $section->id;
        $topic->created_at = Carbon::now();
        $topic->user_id = $this->currentUser->id();
        $topic->user_name = $this->currentUser->user()->name;
        $topic->name = $topicName;
        $topic->slug = $this->topicSlugService->generateUniqueSlug($topicName, $section->id);
        $topic->meta_keywords = $metaKeywords;
        $topic->meta_description = $metaDescription;
        $topic->last_post_date = time();
        $topic->view_count = 0;
        $topic->post_count = 0;
        $topic->curators = $section->access === 1 ? [$this->currentUser->id() => $this->currentUser->user()->name] : [];

        $this->topicRepository->save($topic);

        $message = new ForumMessage();
        $message->topic_id = $topic->id;
        $message->date = time();
        $message->user_id = $this->currentUser->id();
        $message->user_name = $this->currentUser->user()->name;
        $message->ip = $clientInfo->ip;
        $message->ip_via_proxy = $clientInfo->ipViaProxy;
        $message->user_agent = $clientInfo->userAgent;
        $message->text = $messageText;
        $message->save();

        $this->topicStatsRecalculator->recalculate($topic->id);

        $this->currentUser->user()->update(
            [
                'postforum' => ($this->currentUser->user()->postforum + 1),
                'lastpost'  => time(),
            ]
        );

        $unread = new ForumUnread();
        $unread->topic_id = $topic->id;
        $unread->user_id = $this->currentUser->id();
        $unread->time = time();
        $unread->save();

        return new NewTopicResultDTO(
            topicId:   $topic->id,
            messageId: $message->id,
            topicUrl:  $topic->url,
        );
    }
}
