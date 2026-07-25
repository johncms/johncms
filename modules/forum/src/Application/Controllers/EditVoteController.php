<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumValidationException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\Services\ForumTopicPathService;
use Johncms\Modules\Forum\Application\UseCases\DeleteVoteAnswerUseCase;
use Johncms\Modules\Forum\Application\UseCases\GetEditVoteContextUseCase;
use Johncms\Modules\Forum\Application\UseCases\UpdateVoteUseCase;
use Johncms\Http\Request;
use Johncms\System\View\Render;
use Symfony\Component\HttpFoundation\Response;

final readonly class EditVoteController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private ForumErrorRenderer $forumErrorRenderer,
        private GetEditVoteContextUseCase $contextUseCase,
        private UpdateVoteUseCase $updateVoteUseCase,
        private DeleteVoteAnswerUseCase $deleteVoteAnswerUseCase,
        private ForumTopicPathService $topicPathService,
    ) {
        $this->controllerContext->initModule('forum');
    }

    public function __invoke(int $id): Response
    {
        try {
            $context = $this->contextUseCase->execute($id);
        } catch (ForumAccessDeniedException $exception) {
            return $this->forumErrorRenderer->render(
                $this->render,
                $exception,
                [
                    'back_url'      => '/forum/',
                    'back_url_name' => __('Back'),
                ]
            );
        } catch (ForumValidationException $exception) {
            return $this->forumErrorRenderer->render(
                $this->render,
                $exception,
                [
                    'title'         => __('Edit Poll'),
                    'page_title'    => __('Edit Poll'),
                    'message'       => __('Wrong data'),
                    'back_url'      => '/forum/',
                    'back_url_name' => __('Back'),
                ]
            );
        }

        $deleteAnswer = $this->request->query->has('delvote');
        $voteId = abs($this->request->queryInt('vote', 0));
        if ($deleteAnswer && $voteId > 0) {
            if ($this->request->query->has('yes')) {
                $this->deleteVoteAnswerUseCase->execute($context->topicId, $voteId);
                redirect('/forum/editvote/' . $context->topicId . '/');
            }

            return new Response(
                $this->render->render(
                    'forum::delete_answer',
                    [
                        'title'      => __('Delete Answer'),
                        'page_title' => __('Delete Answer'),
                        'id'         => $context->topicId,
                        'delete_url' => '/forum/editvote/' . $context->topicId . '/?vote=' . $voteId . '&amp;delvote&amp;yes',
                        'back_url'   => '/forum/editvote/' . $context->topicId . '/',
                    ]
                )
            );
        }

        if ($this->request->hasBody('submit')) {
            $pollName = mb_substr(trim($this->request->body('name_vote', '')), 0, 200);

            $existingAnswers = [];
            foreach ($context->answers as $answer) {
                $inputName = $answer->id . 'vote';
                $existingAnswers[$answer->id] = mb_substr(trim($this->request->body($inputName, '')), 0, 150);
            }

            $newAnswers = [];
            for ($vote = $context->savedVote; $vote < 20; $vote++) {
                $value = mb_substr(trim($this->request->body((string) $vote, '')), 0, 150);
                if ($value === '') {
                    continue;
                }
                $newAnswers[] = $value;
            }

            $this->updateVoteUseCase->execute($context->topicId, $pollName, $existingAnswers, $newAnswers);

            return new Response(
                $this->render->render(
                    'system::pages/result',
                    [
                        'title'         => __('Edit Poll'),
                        'page_title'    => __('Edit Poll'),
                        'type'          => 'alert-success',
                        'message'       => __('Poll changed'),
                        'back_url'      => $this->topicPathService->getTopicUrlById($context->topicId) ?? '/forum/',
                        'back_url_name' => __('Continue'),
                    ]
                )
            );
        }

        $countVote = $this->request->bodyInt('count_vote', $context->savedVote);
        if ($context->savedVote < 20) {
            if ($this->request->hasBody('plus')) {
                $countVote++;
            } elseif ($this->request->hasBody('minus')) {
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
                'input_value' => htmlentities($this->request->body((string) $vote, ''), ENT_QUOTES, 'UTF-8'),
            ];
        }

        return new Response(
            $this->render->render(
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
            )
        );
    }
}
