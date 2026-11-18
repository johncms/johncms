<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Application\Exceptions\ForumValidationException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\Services\ForumTopicBreadcrumbs;
use Johncms\Modules\Forum\Application\Services\ForumTopicPathService;
use Johncms\Modules\Forum\Application\UseCases\GetEditVoteContextUseCase;
use Johncms\Modules\Forum\Application\UseCases\UpdateVoteUseCase;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;

final readonly class EditVoteController
{
    private const int MAX_ANSWERS = 20;

    public function __construct(
        private ForumErrorRenderer $forumErrorRenderer,
        private GetEditVoteContextUseCase $contextUseCase,
        private UpdateVoteUseCase $updateVoteUseCase,
        private ForumTopicPathService $topicPathService,
        private ForumTopicRepositoryInterface $topics,
        private ForumTopicBreadcrumbs $breadcrumbs,
        private Session $session,
    ) {
    }

    public function __invoke(Request $request, int $id): ViewResponse
    {
        try {
            $context = $this->contextUseCase->execute($id);
        } catch (ForumAccessDeniedException $exception) {
            return $this->forumErrorRenderer->viewResponse(
                $exception,
                [
                    'back_url'      => '/forum/',
                    'back_url_name' => __('Back'),
                ]
            );
        } catch (ForumValidationException | ForumNotFoundException $exception) {
            return $this->forumErrorRenderer->viewResponse(
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

        $sent = $request->getMethod() === 'POST';
        $countVote = $request->bodyInt('count_vote', $context->savedVote);
        if ($request->hasBody('plus')) {
            $countVote++;
        } elseif ($request->hasBody('minus')) {
            $countVote--;
        }
        $countVote = max($context->savedVote, min(self::MAX_ANSWERS, $countVote));

        $pollName = $sent ? mb_substr(trim($request->body('name_vote')), 0, 200) : $context->pollName;
        $existing = [];
        foreach ($context->answers as $answer) {
            $existing[$answer->id] = $sent ? mb_substr(trim($request->body($answer->id . 'vote')), 0, 150) : $answer->name;
        }
        $new = [];
        for ($vote = $context->savedVote; $vote < $countVote; $vote++) {
            $new[$vote] = mb_substr(trim($request->body((string) $vote)), 0, 150);
        }

        $errors = [];
        if ($request->hasBody('submit')) {
            if ($pollName === '') {
                $errors['name_vote'] = [__('Enter the question of the poll.')];
            }
            if (in_array('', $existing, true)) {
                $errors['answers'] = [__('A saved answer cannot be left empty: delete it with its button.')];
            }

            if ($errors === []) {
                $this->updateVoteUseCase->execute($context->topicId, $pollName, $existing, array_values(array_filter($new, static fn (string $answer): bool => $answer !== '')));
                $this->session->flash(ForumTopicController::MESSAGE, __('Poll changed'));
                redirect($this->topicPathService->getTopicUrlById($context->topicId) ?? '/forum/');
            }
        }

        $votes = [];
        $number = 0;
        foreach ($context->answers as $answer) {
            $votes[] = [
                'input_name'  => $answer->id . 'vote',
                'input_label' => __('Answer') . ' ' . ++$number,
                'input_value' => $existing[$answer->id],
                // A poll keeps two answers at least.
                'delete_url'  => $context->savedVote > 2 ? '/forum/editvote/' . $context->topicId . '/answers/' . $answer->id . '/delete' : '',
            ];
        }
        foreach ($new as $vote => $answer) {
            $votes[] = [
                'input_name'  => $vote,
                'input_label' => __('Answer') . ' ' . ++$number,
                'input_value' => $answer,
                'delete_url'  => '',
            ];
        }

        $topic = $this->topics->findById($context->topicId);
        $topicUrl = $this->topicPathService->getTopicUrlById($context->topicId) ?? '/forum/';
        if ($topic !== null) {
            $this->breadcrumbs->add($topic, $topicUrl, __('Edit Poll'));
        }

        return new ViewResponse(
            '@forum/public/edit-poll.twig',
            [
                'title'      => __('Edit Poll'),
                'page_title' => __('Edit Poll'),
                'action_url' => '/forum/editvote/' . $context->topicId . '/',
                'back_url'   => $topicUrl,
                'topic_name' => $topic->name ?? '',
                'saved_vote' => $context->savedVote,
                'count_vote' => $countVote,
                'poll_name'  => $pollName,
                'votes'      => $votes,
                'errors'     => $errors,
            ],
            $errors !== [] ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK,
        );
    }
}
