<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\Controllers;

use Johncms\Auth\CurrentUser;
use Johncms\Http\View\ViewResponse;
use Johncms\Http\Pagination\Pagination;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Profile\Application\DTO\BanHistoryDTO;
use Johncms\Modules\Profile\Application\Exceptions\ProfileAccessForbiddenException;
use Johncms\Modules\Profile\Application\Exceptions\ProfileNotFoundException;
use Johncms\Modules\Profile\Application\UseCases\GetBanFormContextUseCase;
use Johncms\Modules\Profile\Application\UseCases\GetBanHistoryUseCase;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Users\Bans\BanAccess;
use Johncms\Users\Bans\BanException;
use Johncms\Users\Bans\BanManager;
use Johncms\Users\Bans\BanNotFoundException;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\Response;

final readonly class BanController
{
    public function __construct(
        private NavChain $navChain,
        private CurrentUser $currentUser,
        private BanAccess $banAccess,
        private GetBanHistoryUseCase $getBanHistoryUseCase,
        private GetBanFormContextUseCase $getBanFormContextUseCase,
        private BanManager $bans,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
    }

    public function history(int $id): ViewResponse
    {
        try {
            $pagination = $this->paginationFactory->create($this->getBanHistoryUseCase->count($id));

            $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
            if ($redirectUrl !== null) {
                redirect($redirectUrl);
            }

            $dto = $this->getBanHistoryUseCase->getPage($id, $pagination->getPerPage(), $pagination->getOffset());
        } catch (ProfileNotFoundException $e) {
            return $this->renderError($e->getMessage());
        }

        return $this->renderHistory($id, $dto, $pagination);
    }

    public function createForm(int $id): ViewResponse
    {
        try {
            $target = $this->getBanFormContextUseCase->execute($id);
        } catch (ProfileNotFoundException $e) {
            return $this->renderError($e->getMessage());
        } catch (ProfileAccessForbiddenException $e) {
            return $this->renderError($e->getMessage(), 403);
        }

        return $this->renderForm($target);
    }

    public function create(Request $request, int $id): ViewResponse
    {
        try {
            $target = $this->getBanFormContextUseCase->execute($id);
        } catch (ProfileNotFoundException $e) {
            return $this->renderError($e->getMessage());
        } catch (ProfileAccessForbiddenException $e) {
            return $this->renderError($e->getMessage(), 403);
        }

        try {
            $this->bans->apply(
                $target,
                $request->bodyInt('term'),
                $request->bodyInt('time'),
                $request->bodyInt('timeval'),
                $request->body('reason', ''),
            );
        } catch (BanException $e) {
            return $this->renderResult($e->getMessage(), 'alert-danger', '/profile/' . $id);
        }

        return $this->renderResult(__('User banned'), 'alert-success', '/profile/' . $id);
    }

    public function cancelForm(int $id, int $banId): ViewResponse
    {
        if ($error = $this->liftGuard($id)) {
            return $error;
        }

        try {
            $this->bans->findActive($id, $banId);
        } catch (BanNotFoundException) {
            return $this->renderError(__('Wrong data'));
        } catch (BanException $e) {
            return $this->renderError($e->getMessage());
        }

        return $this->renderConfirm(
            __('Ban termination'),
            __('Ban time is going to the end. Infrigement will be saved in the bans history'),
            __('Terminate Ban'),
            '/profile/' . $id . '/bans/' . $banId . '/cancel',
            '/profile/' . $id . '/bans'
        );
    }

    public function cancel(int $id, int $banId): ViewResponse
    {
        if ($error = $this->liftGuard($id)) {
            return $error;
        }

        try {
            $ban = $this->bans->findActive($id, $banId);
        } catch (BanNotFoundException) {
            return $this->renderError(__('Wrong data'));
        } catch (BanException $e) {
            return $this->renderError($e->getMessage());
        }

        $this->bans->lift($ban);

        return $this->renderResult(__('Ban terminated'), 'alert-success', '/profile/' . $id . '/bans', __('Ban termination'));
    }

    public function deleteForm(int $id, int $banId): ViewResponse
    {
        if ($error = $this->destroyGuard($id)) {
            return $error;
        }

        try {
            $this->bans->find($id, $banId);
        } catch (BanNotFoundException) {
            return $this->renderError(__('Wrong data'));
        }

        return $this->renderConfirm(
            __('Delete ban'),
            __('Removing ban along with a record in the bans history'),
            __('Delete'),
            '/profile/' . $id . '/bans/' . $banId . '/delete',
            '/profile/' . $id . '/bans'
        );
    }

    public function delete(int $id, int $banId): ViewResponse
    {
        if ($error = $this->destroyGuard($id)) {
            return $error;
        }

        try {
            $ban = $this->bans->find($id, $banId);
        } catch (BanNotFoundException) {
            return $this->renderError(__('Wrong data'));
        }

        $this->bans->delete($ban);

        return $this->renderResult(__('Ban deleted'), 'alert-success', '/profile/' . $id . '/bans', __('Delete ban'));
    }

    public function clearForm(int $id): ViewResponse
    {
        if ($error = $this->destroyGuard($id)) {
            return $error;
        }

        return $this->renderConfirm(
            __('Violations history'),
            __('Are you sure want to clean entire history of user violations?'),
            __('Clear'),
            '/profile/' . $id . '/bans/clear',
            '/profile/' . $id . '/bans'
        );
    }

    public function clear(int $id): ViewResponse
    {
        if ($error = $this->destroyGuard($id)) {
            return $error;
        }

        $this->bans->clearHistory($id);

        return $this->renderResult(__('Violations history cleared'), 'alert-success', '/profile/' . $id . '/bans', __('Violations history'));
    }

    private function renderHistory(int $profileId, BanHistoryDTO $dto, Pagination $pagination): ViewResponse
    {
        $title = __('Violations History');

        $this->navChain->add(__('User Profile'), '/profile/' . $profileId);
        $this->navChain->add($title);

        return new ViewResponse(
            '@profile/public/ban-history.twig',
            [
                'title'             => $title,
                'page_title'        => $title,
                'user_name'         => $dto->userName,
                'items'             => $dto->items,
                'total'             => $pagination->getTotal(),
                'pagination'        => $pagination->hasPages() ? $pagination->render() : null,
                'clear_history_url' => $dto->clearHistoryUrl,
                'back_url'          => $dto->backUrl,
            ]
        );
    }

    private function renderForm(User $target): ViewResponse
    {
        $title = __('Ban the User');

        $this->navChain->add(__('User Profile'), '/profile/' . $target->id);
        $this->navChain->add($title);

        return new ViewResponse(
            '@profile/public/ban.twig',
            [
                'field_height' => $this->currentUser->user()->config->fieldHeight,
                'title'        => $title,
                'page_title'   => $title,
                'form_action'  => '/profile/' . $target->id . '/bans/new',
                'back_url'     => '/profile/' . $target->id,
                'user_login'   => $target->name,
            ]
        );
    }

    private function renderConfirm(string $title, string $message, string $submitName, string $formAction, string $backUrl): ViewResponse
    {
        $this->navChain->add($title);

        return new ViewResponse(
            '@profile/public/ban-cancel.twig',
            [
                'title'       => $title,
                'page_title'  => $title,
                'message'     => $message,
                'submit_name' => $submitName,
                'form_action' => $formAction,
                'back_url'    => $backUrl,
            ]
        );
    }

    private function renderResult(string $message, string $type, string $backUrl, ?string $title = null): ViewResponse
    {
        return new ViewResponse(
            '@theme/pages/result.twig',
            [
                'title'    => $title ?? __('Ban the User'),
                'type'     => $type,
                'message'  => $message,
                'back_url' => $backUrl,
            ]
        );
    }

    private function renderError(string $message, int $statusCode = 200): ViewResponse
    {
        return new ViewResponse(
            '@theme/pages/result.twig',
            [
                'title'   => __('Ban the User'),
                'type'    => 'alert-danger',
                'message' => $message,
            ],
            $statusCode
        );
    }

    private function liftGuard(int $targetId): ?ViewResponse
    {
        if (! $this->banAccess->mayLift($targetId)) {
            return $this->renderError(__('Wrong data'), 403);
        }

        return null;
    }

    private function destroyGuard(int $targetId): ?ViewResponse
    {
        if (! $this->banAccess->mayDestroy($targetId)) {
            return $this->renderError(__('Wrong data'), 403);
        }

        return null;
    }
}
