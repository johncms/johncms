<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\Controllers;

use Johncms\Http\View\ViewResponse;
use Johncms\Http\Pagination\Pagination;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Profile\Application\DTO\KarmaListDTO;
use Johncms\Modules\Profile\Application\DTO\VoteContextDTO;
use Johncms\Modules\Profile\Application\DTO\VoteKarmaCommand;
use Johncms\Modules\Profile\Application\Exceptions\KarmaVoteException;
use Johncms\Modules\Profile\Application\Exceptions\ProfileNotFoundException;
use Johncms\Modules\Profile\Application\UseCases\CleanKarmaUseCase;
use Johncms\Modules\Profile\Application\UseCases\DeleteKarmaVoteUseCase;
use Johncms\Modules\Profile\Application\UseCases\GetKarmaListUseCase;
use Johncms\Modules\Profile\Application\UseCases\GetNewKarmaUseCase;
use Johncms\Modules\Profile\Application\UseCases\GetVoteContextUseCase;
use Johncms\Modules\Profile\Application\UseCases\VoteKarmaUseCase;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Users\User;
use Johncms\Validator\Validator;
use Symfony\Component\HttpFoundation\Response;

final readonly class KarmaController
{
    public function __construct(
        private NavChain $navChain,
        private User $currentUser,
        private GetKarmaListUseCase $getKarmaListUseCase,
        private GetNewKarmaUseCase $getNewKarmaUseCase,
        private GetVoteContextUseCase $getVoteContextUseCase,
        private VoteKarmaUseCase $voteKarmaUseCase,
        private DeleteKarmaVoteUseCase $deleteKarmaVoteUseCase,
        private CleanKarmaUseCase $cleanKarmaUseCase,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
    }

    public function index(Request $request, int $id): ViewResponse
    {
        $this->ensureKarmaEnabled();

        $type = $this->resolveType($request);

        try {
            $pagination = $this->paginationFactory->create($this->getKarmaListUseCase->count($id, $type));

            $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
            if ($redirectUrl !== null) {
                redirect($redirectUrl);
            }

            $dto = $this->getKarmaListUseCase->getPage($id, $type, $pagination->getPerPage(), $pagination->getOffset());
        } catch (ProfileNotFoundException $e) {
            return $this->renderError($e->getMessage());
        }

        return $this->renderList(__('Karma'), $id, $dto, $pagination);
    }

    public function newResponses(int $id): ViewResponse
    {
        $this->ensureKarmaEnabled();

        $pagination = $this->paginationFactory->create($this->getNewKarmaUseCase->count());

        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $dto = $this->getNewKarmaUseCase->getPage($pagination->getPerPage(), $pagination->getOffset());

        return $this->renderList(__('New responses'), $id, $dto, $pagination);
    }

    public function voteForm(int $id): ViewResponse
    {
        $this->ensureKarmaEnabled();

        try {
            $context = $this->getVoteContextUseCase->execute($id);
        } catch (ProfileNotFoundException $e) {
            return $this->renderError($e->getMessage());
        } catch (KarmaVoteException $e) {
            return $this->renderVoteErrors($e->getErrors(), $id);
        }

        return $this->renderVoteForm($context);
    }

    public function vote(Request $request, int $id): ViewResponse
    {
        $this->ensureKarmaEnabled();

        try {
            $context = $this->getVoteContextUseCase->execute($id);
        } catch (ProfileNotFoundException $e) {
            return $this->renderError($e->getMessage());
        } catch (KarmaVoteException $e) {
            return $this->renderVoteErrors($e->getErrors(), $id);
        }

        $command = new VoteKarmaCommand(
            type: $request->bodyInt('type'),
            points: $request->bodyInt('points'),
            text: $request->body('text', ''),
        );
        $this->voteKarmaUseCase->execute($command, $context);

        return new ViewResponse(
            '@theme/pages/result.twig',
            [
                'title'         => __('Karma'),
                'type'          => 'alert-success',
                'message'       => __('You have successfully voted'),
                'back_url'      => '/profile/' . $context->targetId,
                'back_url_name' => __('Continue'),
            ]
        );
    }

    public function deleteForm(Request $request, int $id, int $voteId): ViewResponse
    {
        $this->ensureKarmaEnabled();
        if ($error = $this->supervisorGuard()) {
            return $error;
        }

        try {
            $this->deleteKarmaVoteUseCase->getVote($id, $voteId);
        } catch (ProfileNotFoundException) {
            return $this->renderError(__('Wrong data'));
        }

        $type = $this->resolveType($request);

        return $this->renderConfirm(
            __('Do you really want to delete comment?'),
            '/profile/' . $id . '/karma/delete/' . $voteId . '?type=' . $type,
            '/profile/' . $id . '/karma?type=' . $type
        );
    }

    public function delete(Request $request, int $id, int $voteId): ViewResponse
    {
        $this->ensureKarmaEnabled();
        if ($error = $this->supervisorGuard()) {
            return $error;
        }
        if (! $this->isCsrfValid($request)) {
            return $this->renderError(__('Wrong data'));
        }

        try {
            $vote = $this->deleteKarmaVoteUseCase->getVote($id, $voteId);
        } catch (ProfileNotFoundException) {
            return $this->renderError(__('Wrong data'));
        }

        $this->deleteKarmaVoteUseCase->delete($vote);

        redirect('/profile/' . $id . '/karma?type=' . $this->resolveType($request));
    }

    public function cleanForm(int $id): ViewResponse
    {
        $this->ensureKarmaEnabled();
        if ($error = $this->supervisorGuard()) {
            return $error;
        }

        return $this->renderConfirm(
            __('Do you really want to delete all reviews about user?'),
            '/profile/' . $id . '/karma/clean',
            '/profile/' . $id . '/karma'
        );
    }

    public function clean(Request $request, int $id): ViewResponse
    {
        $this->ensureKarmaEnabled();
        if ($error = $this->supervisorGuard()) {
            return $error;
        }
        if (! $this->isCsrfValid($request)) {
            return $this->renderError(__('Wrong data'));
        }

        $this->cleanKarmaUseCase->execute($id);

        redirect('/profile/' . $id);
    }

    private function renderList(string $title, int $profileId, KarmaListDTO $dto, Pagination $pagination): ViewResponse
    {
        $this->navChain->add(__('User Profile'), '/profile/' . $profileId);
        $this->navChain->add(__('Karma'));

        return new ViewResponse(
            '@profile/public/karma.twig',
            [
                'title'      => $title,
                'page_title' => $title,
                'filters'    => $dto->filters,
                'items'      => $dto->items,
                'total'      => $pagination->getTotal(),
                'pagination' => $pagination->hasPages() ? $pagination->render() : null,
                'reset_url'  => $dto->resetUrl,
                'back_url'   => $dto->backUrl,
            ]
        );
    }

    private function renderVoteForm(VoteContextDTO $context): ViewResponse
    {
        $title = __('Karma');

        $this->navChain->add(__('User Profile'), '/profile/' . $context->targetId);
        $this->navChain->add(__('Karma'));

        return new ViewResponse(
            '@profile/public/karma-vote.twig',
            [
                'field_height' => $this->currentUser->config->fieldHeight,
                'title'        => $title,
                'page_title'   => $title,
                'options'      => range(1, $context->availablePoints),
                'vote_title'   => __('Vote for') . ': ' . $context->targetName,
                'form_action'  => '/profile/' . $context->targetId . '/karma/vote',
                'back_url'     => '/profile/' . $context->targetId,
            ]
        );
    }

    private function renderConfirm(string $message, string $formAction, string $backUrl): ViewResponse
    {
        $title = __('Karma');

        $this->navChain->add(__('User Profile'), $backUrl);
        $this->navChain->add(__('Karma'));

        return new ViewResponse(
            '@profile/public/confirm-delete.twig',
            [
                'title'       => $title,
                'page_title'  => $title,
                'message'     => $message,
                'form_action' => $formAction,
                'back_url'    => $backUrl,
            ]
        );
    }

    private function renderVoteErrors(array $errors, int $profileId): ViewResponse
    {
        return new ViewResponse(
            '@theme/pages/result.twig',
            [
                'title'         => __('Karma'),
                'type'          => 'alert-danger',
                'message'       => $errors,
                'back_url'      => '/profile/' . $profileId,
                'back_url_name' => __('Back'),
            ]
        );
    }

    private function renderError(string $message, int $status = 200): ViewResponse
    {
        return new ViewResponse(
            '@theme/pages/result.twig',
            [
                'title'   => __('Karma'),
                'type'    => 'alert-danger',
                'message' => $message,
            ],
            $status
        );
    }

    private function supervisorGuard(): ?ViewResponse
    {
        if ($this->currentUser->rights !== 9) {
            return $this->renderError(__('Access forbidden'), 403);
        }

        return null;
    }

    private function ensureKarmaEnabled(): void
    {
        if (empty(config('johncms')['karma']['on'])) {
            pageNotFound();
        }
    }

    private function isCsrfValid(Request $request): bool
    {
        $validator = new Validator(
            ['csrf_token' => $request->body('csrf_token', '')],
            ['csrf_token' => ['Csrf']]
        );

        return $validator->isValid();
    }

    private function resolveType(Request $request): int
    {
        return $request->queryInt('type');
    }
}
