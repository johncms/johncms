<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Forum\Application\Exceptions\AccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\EditVoteWrongDataException;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Services\ForumAccessResponseBuilder;
use Johncms\Modules\Forum\Application\Services\ForumTopicPathService;
use Johncms\Modules\Forum\Application\UseCases\DeleteVoteAnswerUseCase;
use Johncms\Modules\Forum\Application\UseCases\EnsureEditVoteAccessUseCase;
use Johncms\Modules\Forum\Application\UseCases\EnsureForumAccessUseCase;
use Johncms\Modules\Forum\Application\UseCases\GetEditVoteContextUseCase;
use Johncms\Modules\Forum\Application\UseCases\UpdateVoteUseCase;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;

final readonly class EditVoteController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private EnsureForumAccessUseCase $forumAccessUseCase,
        private ForumAccessResponseBuilder $forumAccessResponseBuilder,
        private EnsureEditVoteAccessUseCase $accessUseCase,
        private GetEditVoteContextUseCase $contextUseCase,
        private UpdateVoteUseCase $updateVoteUseCase,
        private DeleteVoteAnswerUseCase $deleteVoteAnswerUseCase,
        private ForumTopicPathService $topicPathService,
    ) {
        $this->controllerContext->initModule('forum');
    }

    public function __invoke(int $id): string
    {
        try {
            $this->forumAccessUseCase->execute();
        } catch (ForumAccessDeniedException $exception) {
            return $this->render->render(
                'system::pages/result',
                $this->forumAccessResponseBuilder->forException($exception)
            );
        }

        try {
            $this->accessUseCase->execute($id);
            $context = $this->contextUseCase->execute($id);
        } catch (AccessDeniedException) {
            http_response_code(403);
            return $this->render->render(
                'system::pages/result',
                [
                    'title'         => __('Access forbidden'),
                    'type'          => 'alert-danger',
                    'message'       => __('Access forbidden'),
                    'back_url'      => '/forum/',
                    'back_url_name' => __('Back'),
                ]
            );
        } catch (EditVoteWrongDataException) {
            return $this->render->render(
                'system::pages/result',
                [
                    'title'         => __('Edit Poll'),
                    'page_title'    => __('Edit Poll'),
                    'type'          => 'alert-danger',
                    'message'       => __('Wrong data'),
                    'back_url'      => '/forum/',
                    'back_url_name' => __('Back'),
                ]
            );
        }

        $deleteAnswer = $this->request->getQuery('delvote', null) !== null;
        $voteId = abs((int) $this->request->getQuery('vote', 0));
        if ($deleteAnswer && $voteId > 0) {
            if ($this->request->getQuery('yes', null) !== null) {
                $this->deleteVoteAnswerUseCase->execute($context->topicId, $voteId);
                redirect('/forum/editvote/' . $context->topicId . '/');
            }

            return $this->render->render(
                'forum::delete_answer',
                [
                    'title'      => __('Delete Answer'),
                    'page_title' => __('Delete Answer'),
                    'id'         => $context->topicId,
                    'delete_url' => '/forum/editvote/' . $context->topicId . '/?vote=' . $voteId . '&amp;delvote&amp;yes',
                    'back_url'   => '/forum/editvote/' . $context->topicId . '/',
                ]
            );
        }

        if ($this->request->getPost('submit', null) !== null) {
            $pollName = mb_substr(trim((string) $this->request->getPost('name_vote', '')), 0, 200);

            $existingAnswers = [];
            foreach ($context->answers as $answer) {
                $inputName = $answer->id . 'vote';
                $existingAnswers[$answer->id] = mb_substr(trim((string) $this->request->getPost($inputName, '')), 0, 150);
            }

            $newAnswers = [];
            for ($vote = $context->savedVote; $vote < 20; $vote++) {
                $value = mb_substr(trim((string) $this->request->getPost((string) $vote, '')), 0, 150);
                if ($value === '') {
                    continue;
                }
                $newAnswers[] = $value;
            }

            $this->updateVoteUseCase->execute($context->topicId, $pollName, $existingAnswers, $newAnswers);

            return $this->render->render(
                'system::pages/result',
                [
                    'title'         => __('Edit Poll'),
                    'page_title'    => __('Edit Poll'),
                    'type'          => 'alert-success',
                    'message'       => __('Poll changed'),
                    'back_url'      => $this->topicPathService->getTopicUrlById($context->topicId) ?? '/forum/',
                    'back_url_name' => __('Continue'),
                ]
            );
        }

        $countVote = (int) $this->request->getPost('count_vote', $context->savedVote);
        if ($context->savedVote < 20) {
            if ($this->request->getPost('plus', null) !== null) {
                $countVote++;
            } elseif ($this->request->getPost('minus', null) !== null) {
                $countVote--;
            }

            if (empty($countVote)) {
                $countVote = $context->savedVote;
            } elseif ($countVote > 20) {
                $countVote = 20;
            }
        }

        if ($countVote < $context->savedVote) {
            $countVote = $context->savedVote;
        }

        $votes = [];
        $i = 0;
        foreach ($context->answers as $answer) {
            $votes[] = [
                'input_name'  => $answer->id . 'vote',
                'input_label' => __('Answer') . ' ' . ($i + 1),
                'input_value' => htmlentities($answer->name, ENT_QUOTES, 'UTF-8'),
                'delete_url'  => $context->savedVote > 2
                    ? '/forum/editvote/' . $context->topicId . '/?vote=' . $answer->id . '&amp;delvote'
                    : '',
            ];
            $i++;
        }

        for ($vote = $i; $vote < $countVote; $vote++) {
            $votes[] = [
                'input_name'  => $vote,
                'input_label' => __('Answer') . ' ' . ($vote + 1),
                'input_value' => htmlentities((string) $this->request->getPost((string) $vote, ''), ENT_QUOTES, 'UTF-8'),
            ];
        }

        return $this->render->render(
            'forum::edit_poll',
            [
                'title'      => __('Edit Poll'),
                'page_title' => __('Edit Poll'),
                'id'         => $context->topicId,
                'back_url'   => $this->topicPathService->getTopicUrlById($context->topicId) ?? '/forum/',
                'saved_vote' => $context->savedVote,
                'count_vote' => $countVote,
                'poll_name'  => htmlentities($context->pollName, ENT_QUOTES, 'UTF-8'),
                'votes'      => $votes,
            ]
        );
    }
}
