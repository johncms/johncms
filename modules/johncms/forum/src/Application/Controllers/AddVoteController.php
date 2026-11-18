<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumValidationException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\Services\ForumTopicBreadcrumbs;
use Johncms\Modules\Forum\Application\Services\ForumTopicPathService;
use Johncms\Modules\Forum\Application\UseCases\CreateVoteUseCase;
use Johncms\Modules\Forum\Application\UseCases\GetAddVoteContextUseCase;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;

final readonly class AddVoteController
{
    private const int MIN_ANSWERS = 2;
    private const int MAX_ANSWERS = 20;

    public function __construct(
        private ForumErrorRenderer $forumErrorRenderer,
        private GetAddVoteContextUseCase $contextUseCase,
        private CreateVoteUseCase $createVoteUseCase,
        private ForumTopicPathService $topicPathService,
        private ForumTopicRepositoryInterface $topics,
        private ForumTopicBreadcrumbs $breadcrumbs,
        private Session $session,
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

        $countVote = $request->bodyInt('count_vote', self::MIN_ANSWERS);
        if ($request->hasBody('plus')) {
            $countVote++;
        } elseif ($request->hasBody('minus')) {
            $countVote--;
        }
        $countVote = max(self::MIN_ANSWERS, min(self::MAX_ANSWERS, $countVote));

        $pollName = mb_substr(trim($request->body('name_vote')), 0, 200);
        $answers = [];
        for ($vote = 0; $vote < $countVote; $vote++) {
            $answers[$vote] = mb_substr(trim($request->body((string) $vote)), 0, 150);
        }

        $errors = [];
        if ($request->hasBody('submit')) {
            $filled = array_values(array_filter($answers, static fn (string $answer): bool => $answer !== ''));
            if ($pollName === '') {
                $errors['name_vote'] = [__('Enter the question of the poll.')];
            }
            if (count($filled) < self::MIN_ANSWERS) {
                $errors['answers'] = [__('A poll needs two answers at least.')];
            }

            if ($errors === []) {
                $this->createVoteUseCase->execute($topicId, $pollName, $filled);
                $this->session->flash(ForumTopicController::MESSAGE, __('Poll added'));
                redirect($this->topicPathService->getTopicUrlById($topicId) ?? '/forum/');
            }
        }

        $topic = $this->topics->findById($topicId);
        $topicUrl = $this->topicPathService->getTopicUrlById($topicId) ?? '/forum/';
        if ($topic !== null) {
            $this->breadcrumbs->add($topic, $topicUrl, __('Add Poll'));
        }

        $votes = [];
        foreach ($answers as $vote => $answer) {
            $votes[] = [
                'input_name'  => $vote,
                'input_label' => __('Answer') . ' ' . ($vote + 1),
                'input_value' => $answer,
            ];
        }

        return new ViewResponse(
            '@forum/public/add-poll.twig',
            [
                'title'      => __('Add Poll'),
                'page_title' => __('Add Poll'),
                'action_url' => '/forum/addvote/' . $topicId . '/',
                'back_url'   => $topicUrl,
                'topic_name' => $topic->name ?? '',
                'count_vote' => $countVote,
                'poll_name'  => $pollName,
                'votes'      => $votes,
                'errors'     => $errors,
            ],
            $errors !== [] ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK,
        );
    }
}
