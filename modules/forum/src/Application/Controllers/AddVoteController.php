<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumValidationException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\Services\ForumTopicPathService;
use Johncms\Modules\Forum\Application\UseCases\CreateVoteUseCase;
use Johncms\Modules\Forum\Application\UseCases\EnsureAddVoteAccessUseCase;
use Johncms\Modules\Forum\Application\UseCases\EnsureForumAccessUseCase;
use Johncms\Modules\Forum\Application\UseCases\GetAddVoteContextUseCase;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;

final readonly class AddVoteController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private EnsureForumAccessUseCase $forumAccessUseCase,
        private ForumErrorRenderer $forumErrorRenderer,
        private EnsureAddVoteAccessUseCase $accessUseCase,
        private GetAddVoteContextUseCase $contextUseCase,
        private CreateVoteUseCase $createVoteUseCase,
        private ForumTopicPathService $topicPathService,
    ) {
        $this->controllerContext->initModule('forum');
    }

    public function __invoke(int $id): string
    {
        try {
            $this->forumAccessUseCase->execute();
        } catch (ForumAccessDeniedException $exception) {
            return $this->forumErrorRenderer->render($this->render, $exception);
        }

        try {
            $this->accessUseCase->execute($id);
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
                    'title'         => __('Add Poll'),
                    'page_title'    => __('Add Poll'),
                    'message'       => __('Wrong data'),
                    'back_url'      => '/forum/',
                    'back_url_name' => __('Back'),
                ]
            );
        }

        $countVoteRaw = $this->request->getPost('count_vote', 0);
        $countVote = (int) $countVoteRaw;
        if ($this->request->getPost('plus', null) !== null) {
            $countVote++;
        } elseif ($this->request->getPost('minus', null) !== null) {
            $countVote--;
        }
        $countVote = $this->normalizeVoteCount($countVote);

        if ($this->request->getPost('submit', null) !== null) {
            $voteName = mb_substr(trim((string) $this->request->getPost('name_vote', '')), 0, 200);
            $firstAnswer = trim((string) $this->request->getPost('0', ''));
            $secondAnswer = trim((string) $this->request->getPost('1', ''));

            if ($voteName !== '' && $firstAnswer !== '' && $secondAnswer !== '' && ! empty($countVoteRaw)) {
                $answers = [];
                for ($vote = 0; $vote < $countVote; $vote++) {
                    $text = mb_substr(trim((string) $this->request->getPost((string) $vote, '')), 0, 150);
                    if ($text === '') {
                        continue;
                    }
                    $answers[] = $text;
                }

                $this->createVoteUseCase->execute($context->topicId, $voteName, $answers);

                return $this->render->render(
                    'system::pages/result',
                    [
                        'title'         => __('Add Poll'),
                        'page_title'    => __('Add Poll'),
                        'type'          => 'alert-success',
                        'message'       => __('Poll added'),
                        'back_url'      => $this->topicPathService->getTopicUrlById($context->topicId) ?? '/forum/',
                        'back_url_name' => __('Continue'),
                    ]
                );
            }

            return $this->render->render(
                'system::pages/result',
                [
                    'title'         => __('Add Poll'),
                    'page_title'    => __('Add Poll'),
                    'type'          => 'alert-danger',
                    'message'       => __('The required fields are not filled'),
                    'back_url'      => '/forum/addvote/' . $context->topicId . '/',
                    'back_url_name' => __('Repeat'),
                ]
            );
        }

        $votes = [];
        for ($vote = 0; $vote < $countVote; $vote++) {
            $votes[] = [
                'input_name'  => $vote,
                'input_label' => __('Answer') . ' ' . ($vote + 1),
                'input_value' => htmlentities((string) $this->request->getPost((string) $vote, ''), ENT_QUOTES, 'UTF-8'),
            ];
        }

        return $this->render->render(
            'forum::add_poll',
            [
                'title'      => __('Add File'),
                'page_title' => __('Add File'),
                'id'         => $context->topicId,
                'back_url'   => $this->topicPathService->getTopicUrlById($context->topicId) ?? '/forum/',
                'count_vote' => $countVote,
                'poll_name'  => htmlentities((string) $this->request->getPost('name_vote', ''), ENT_QUOTES, 'UTF-8'),
                'votes'      => $votes,
            ]
        );
    }

    private function normalizeVoteCount(int $count): int
    {
        if ($count < 2) {
            return 2;
        }

        if ($count > 20) {
            return 20;
        }

        return $count;
    }
}
