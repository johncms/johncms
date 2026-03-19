<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\DTO\DeletePostResultDTO;
use Johncms\Modules\Forum\Application\DTO\EditPostContextDTO;
use Johncms\Modules\Forum\Application\Services\ForumSectionPathService;
use Johncms\Modules\Forum\Application\Services\ForumTopicPathService;
use Johncms\Modules\Forum\Domain\Repository\ForumFileRepositoryInterface;
use Johncms\Modules\Forum\Domain\Repository\ForumMessageRepositoryInterface;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;
use Johncms\Modules\Forum\Domain\Repository\ForumUnreadRepositoryInterface;
use Johncms\Modules\Forum\Domain\Repository\ForumVoteRepositoryInterface;
use Johncms\System\Legacy\Tools;
use Johncms\Users\User;

final readonly class DeletePostUseCase
{
    public function __construct(
        private ForumMessageRepositoryInterface $messageRepository,
        private ForumTopicRepositoryInterface $topicRepository,
        private ForumFileRepositoryInterface $fileRepository,
        private ForumVoteRepositoryInterface $voteRepository,
        private ForumUnreadRepositoryInterface $unreadRepository,
        private ForumSectionPathService $sectionPathService,
        private ForumTopicPathService $topicPathService,
        private Tools $tools,
        private User $currentUser,
    ) {
    }

    public function execute(EditPostContextDTO $context, bool $hardDelete, array $forumSettings): DeletePostResultDTO
    {
        $message = $context->message;
        $topic = $context->topic;
        $shouldRecountTopic = true;

        if (! $message->deleted) {
            $author = User::query()->find($message->user_id);
            if ($author !== null) {
                $postForum = max(0, $author->postforum - 1);
                $author->update(['postforum' => $postForum]);
            }
        }

        $redirectUrl = $context->backUrl;
        if ($hardDelete && $this->currentUser->rights === 9) {
            $files = $this->fileRepository->getByPostId($message->id);
            foreach ($files as $file) {
                $filePath = UPLOAD_PATH . 'forum/attach/' . $file->filename;
                if (is_file($filePath)) {
                    unlink($filePath);
                }
            }

            $this->fileRepository->deleteByPostId($message->id);

            $strictPageCount = $this->messageRepository->countByTopicIdWithComparison(
                topicId: $topic->id,
                messageId: $message->id,
                upfp: ! empty($forumSettings['upfp']),
                includeDeleted: true,
                strict: true,
            );

            $page = (int) ceil($strictPageCount / $this->currentUser->config->kmess);
            $page = max(1, $page);

            $this->messageRepository->deleteById($message->id);

            if ($context->posts < 2) {
                $this->voteRepository->deleteVotesByTopic($topic->id);
                $this->voteRepository->deleteVoteUsersByTopic($topic->id);
                $this->unreadRepository->deleteByTopicId($topic->id);
                $this->topicRepository->deleteById($topic->id);

                $redirectUrl = $this->sectionPathService->getSectionUrlById($topic->section_id) ?? '/forum/';
                $shouldRecountTopic = false;
            } else {
                $redirectUrl = $this->topicPathService->getTopicUrl($topic, $page > 1 ? $page : null);
            }
        } else {
            $this->fileRepository->markDeletedByPostId($message->id);

            if ($context->posts === 1) {
                $this->topicRepository->markDeleted($topic->id, $this->currentUser->name);
                $redirectUrl = $this->sectionPathService->getSectionUrlById($topic->section_id) ?? '/forum/';
                $shouldRecountTopic = false;
            } else {
                $this->messageRepository->markDeletedById($message->id, $this->currentUser->name);
                $redirectUrl = $this->topicPathService->getTopicUrl($topic, $context->page > 1 ? $context->page : null);
            }
        }

        if ($shouldRecountTopic) {
            $this->tools->recountForumTopic($topic->id);
        }

        return new DeletePostResultDTO($redirectUrl);
    }
}
