<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
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
use Johncms\System\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Johncms\Validator\Validator;

final readonly class KarmaController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private User $currentUser,
        private GetKarmaListUseCase $getKarmaListUseCase,
        private GetNewKarmaUseCase $getNewKarmaUseCase,
        private GetVoteContextUseCase $getVoteContextUseCase,
        private VoteKarmaUseCase $voteKarmaUseCase,
        private DeleteKarmaVoteUseCase $deleteKarmaVoteUseCase,
        private CleanKarmaUseCase $cleanKarmaUseCase,
    ) {
        $this->controllerContext->initModule('profile');
    }

    public function index(int $id): string
    {
        $this->ensureKarmaEnabled();

        try {
            $dto = $this->getKarmaListUseCase->execute($id, $this->resolveType(), (int) $this->currentUser->config->kmess);
        } catch (ProfileNotFoundException $e) {
            return $this->renderError($e->getMessage());
        }

        return $this->renderList(__('Karma'), $id, $dto);
    }

    public function newResponses(int $id): string
    {
        $this->ensureKarmaEnabled();

        $dto = $this->getNewKarmaUseCase->execute((int) $this->currentUser->config->kmess);

        return $this->renderList(__('New responses'), $id, $dto);
    }

    public function voteForm(int $id): string
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

    public function vote(int $id): string
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
            type: (int) $this->request->getPost('type', 0, FILTER_VALIDATE_INT),
            points: (int) $this->request->getPost('points', 0, FILTER_VALIDATE_INT),
            text: (string) $this->request->getPost('text', ''),
        );
        $this->voteKarmaUseCase->execute($command, $context);

        return $this->render->render(
            'system::pages/result',
            [
                'title'         => __('Karma'),
                'type'          => 'alert-success',
                'message'       => __('You have successfully voted'),
                'back_url'      => '/profile/' . $context->targetId,
                'back_url_name' => __('Continue'),
            ]
        );
    }

    public function deleteForm(int $id, int $voteId): string
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

        $type = $this->resolveType();

        return $this->renderConfirm(
            __('Do you really want to delete comment?'),
            '/profile/' . $id . '/karma/delete/' . $voteId . '?type=' . $type,
            '/profile/' . $id . '/karma?type=' . $type
        );
    }

    public function delete(int $id, int $voteId): string
    {
        $this->ensureKarmaEnabled();
        if ($error = $this->supervisorGuard()) {
            return $error;
        }
        if (! $this->isCsrfValid()) {
            return $this->renderError(__('Wrong data'));
        }

        try {
            $vote = $this->deleteKarmaVoteUseCase->getVote($id, $voteId);
        } catch (ProfileNotFoundException) {
            return $this->renderError(__('Wrong data'));
        }

        $this->deleteKarmaVoteUseCase->delete($vote);

        redirect('/profile/' . $id . '/karma?type=' . $this->resolveType());
    }

    public function cleanForm(int $id): string
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

    public function clean(int $id): string
    {
        $this->ensureKarmaEnabled();
        if ($error = $this->supervisorGuard()) {
            return $error;
        }
        if (! $this->isCsrfValid()) {
            return $this->renderError(__('Wrong data'));
        }

        $this->cleanKarmaUseCase->execute($id);

        redirect('/profile/' . $id);
    }

    private function renderList(string $title, int $profileId, KarmaListDTO $dto): string
    {
        $this->navChain->add(__('User Profile'), '/profile/' . $profileId);
        $this->navChain->add(__('Karma'));

        $this->render->addData([
            'title'      => $title,
            'page_title' => $title,
        ]);

        return $this->render->render(
            'profile::karma',
            [
                'title'      => $title,
                'page_title' => $title,
                'data'       => [
                    'filters'    => $dto->filters,
                    'items'      => $dto->items,
                    'total'      => $dto->total,
                    'pagination' => $dto->pagination,
                    'reset_url'  => $dto->resetUrl,
                    'back_url'   => $dto->backUrl,
                ],
            ]
        );
    }

    private function renderVoteForm(VoteContextDTO $context): string
    {
        $title = __('Karma');

        $this->navChain->add(__('User Profile'), '/profile/' . $context->targetId);
        $this->navChain->add(__('Karma'));

        $this->render->addData([
            'title'      => $title,
            'page_title' => $title,
        ]);

        return $this->render->render(
            'profile::karma_vote',
            [
                'title'      => $title,
                'page_title' => $title,
                'data'       => [
                    'options'     => range(1, $context->availablePoints),
                    'vote_title'  => __('Vote for') . ': ' . $context->targetName,
                    'form_action' => '/profile/' . $context->targetId . '/karma/vote',
                    'back_url'    => '/profile/' . $context->targetId,
                ],
            ]
        );
    }

    private function renderConfirm(string $message, string $formAction, string $backUrl): string
    {
        $title = __('Karma');

        $this->navChain->add(__('User Profile'), $backUrl);
        $this->navChain->add(__('Karma'));

        $this->render->addData([
            'title'      => $title,
            'page_title' => $title,
        ]);

        return $this->render->render(
            'profile::karma_delete',
            [
                'title'      => $title,
                'page_title' => $title,
                'data'       => [
                    'message'     => $message,
                    'form_action' => $formAction,
                    'back_url'    => $backUrl,
                ],
            ]
        );
    }

    private function renderVoteErrors(array $errors, int $profileId): string
    {
        return $this->render->render(
            'system::pages/result',
            [
                'title'         => __('Karma'),
                'type'          => 'alert-danger',
                'message'       => $errors,
                'back_url'      => '/profile/' . $profileId,
                'back_url_name' => __('Back'),
            ]
        );
    }

    private function renderError(string $message): string
    {
        return $this->render->render(
            'system::pages/result',
            [
                'title'   => __('Karma'),
                'type'    => 'alert-danger',
                'message' => $message,
            ]
        );
    }

    private function supervisorGuard(): ?string
    {
        if ($this->currentUser->rights !== 9) {
            http_response_code(403);
            return $this->renderError(__('Access forbidden'));
        }

        return null;
    }

    private function ensureKarmaEnabled(): void
    {
        if (empty(config('johncms')['karma']['on'])) {
            pageNotFound();
        }
    }

    private function isCsrfValid(): bool
    {
        $validator = new Validator(
            ['csrf_token' => (string) $this->request->getPost('csrf_token', '')],
            ['csrf_token' => ['Csrf']]
        );

        return $validator->isValid();
    }

    private function resolveType(): int
    {
        return (int) $this->request->getQuery('type', 0, FILTER_VALIDATE_INT);
    }
}
