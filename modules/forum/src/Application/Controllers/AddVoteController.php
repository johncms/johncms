<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumValidationException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\Services\ForumTopicPathService;
use Johncms\Modules\Forum\Application\UseCases\CreateVoteUseCase;
use Johncms\Modules\Forum\Application\UseCases\GetAddVoteContextUseCase;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;

final readonly class AddVoteController
{
    public function __construct(
        private ForumErrorRenderer $forumErrorRenderer,
        private GetAddVoteContextUseCase $contextUseCase,
        private CreateVoteUseCase $createVoteUseCase,
        private ForumTopicPathService $topicPathService,
    ) {
    }

    public function __invoke(Request $request, int $id): ViewResponse
    {
        try {
            $topicId = $this->contextUseCase->execute($id);
        } catch (ForumAccessDeniedException $exception) {
            return $this->forumErrorRenderer->viewResponse(
                $exception,
                [
                    'back_url'      => '/forum/',
                    'back_url_name' => __('Back'),
                ]
            );
        } catch (ForumValidationException $exception) {
            return $this->forumErrorRenderer->viewResponse(
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

        $countVoteRaw = $request->bodyInt('count_vote', 0);
        $countVote = (int) $countVoteRaw;
        if ($request->hasBody('plus')) {
            $countVote++;
        } elseif ($request->hasBody('minus')) {
            $countVote--;
        }
        $countVote = $this->normalizeVoteCount($countVote);

        if ($request->hasBody('submit')) {
            $voteName = mb_substr(trim($request->body('name_vote', '')), 0, 200);
            $firstAnswer = trim($request->body('0', ''));
            $secondAnswer = trim($request->body('1', ''));

            if ($voteName !== '' && $firstAnswer !== '' && $secondAnswer !== '' && ! empty($countVoteRaw)) {
                $answers = [];
                for ($vote = 0; $vote < $countVote; $vote++) {
                    $text = mb_substr(trim($request->body((string) $vote, '')), 0, 150);
                    if ($text === '') {
                        continue;
                    }
                    $answers[] = $text;
                }

                $this->createVoteUseCase->execute($topicId, $voteName, $answers);

                return new ViewResponse(
                    '@theme/pages/result.twig',
                    [
                        'title'         => __('Add Poll'),
                        'page_title'    => __('Add Poll'),
                        'type'          => 'alert-success',
                        'message'       => __('Poll added'),
                        'back_url'      => $this->topicPathService->getTopicUrlById($topicId) ?? '/forum/',
                        'back_url_name' => __('Continue'),
                    ]
                );
            }

            return new ViewResponse(
                '@theme/pages/result.twig',
                [
                    'title'         => __('Add Poll'),
                    'page_title'    => __('Add Poll'),
                    'type'          => 'alert-danger',
                    'message'       => __('The required fields are not filled'),
                    'back_url'      => '/forum/addvote/' . $topicId . '/',
                    'back_url_name' => __('Repeat'),
                ]
            );
        }

        $votes = [];
        for ($vote = 0; $vote < $countVote; $vote++) {
            $votes[] = [
                'input_name'  => $vote,
                'input_label' => __('Answer') . ' ' . ($vote + 1),
                'input_value' => $request->body((string) $vote, ''),
            ];
        }

        return new ViewResponse(
            '@forum/public/add-poll.twig',
            [
                'title'      => __('Add Poll'),
                'page_title' => __('Add Poll'),
                'action_url' => '/forum/addvote/' . $topicId . '/',
                'back_url'   => $this->topicPathService->getTopicUrlById($topicId) ?? '/forum/',
                'count_vote' => $countVote,
                'poll_name'  => $request->body('name_vote', ''),
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
