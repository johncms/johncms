<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\Controllers;

use Johncms\Http\View\ViewResponse;
use Johncms\Http\Pagination\Pagination;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Profile\Application\DTO\BanHistoryDTO;
use Johncms\Modules\Profile\Application\DTO\BanUserCommand;
use Johncms\Modules\Profile\Application\Exceptions\BanException;
use Johncms\Modules\Profile\Application\Exceptions\ProfileAccessForbiddenException;
use Johncms\Modules\Profile\Application\Exceptions\ProfileNotFoundException;
use Johncms\Modules\Profile\Application\UseCases\BanUserUseCase;
use Johncms\Modules\Profile\Application\UseCases\CancelBanUseCase;
use Johncms\Modules\Profile\Application\UseCases\ClearBanHistoryUseCase;
use Johncms\Modules\Profile\Application\UseCases\DeleteBanUseCase;
use Johncms\Modules\Profile\Application\UseCases\GetBanFormContextUseCase;
use Johncms\Modules\Profile\Application\UseCases\GetBanHistoryUseCase;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Users\User;
use Johncms\Validator\Validator;
use Symfony\Component\HttpFoundation\Response;

final readonly class BanController
{
    public function __construct(
        private NavChain $navChain,
        private User $currentUser,
        private GetBanHistoryUseCase $getBanHistoryUseCase,
        private GetBanFormContextUseCase $getBanFormContextUseCase,
        private BanUserUseCase $banUserUseCase,
        private CancelBanUseCase $cancelBanUseCase,
        private DeleteBanUseCase $deleteBanUseCase,
        private ClearBanHistoryUseCase $clearBanHistoryUseCase,
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

    public function createForm(Request $request, int $id): ViewResponse
    {
        try {
            $target = $this->getBanFormContextUseCase->execute($id);
        } catch (ProfileNotFoundException $e) {
            return $this->renderError($e->getMessage());
        } catch (ProfileAccessForbiddenException $e) {
            return $this->renderError($e->getMessage(), 403);
        }

        return $this->renderForm($request, $target);
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

        if (! $this->isCsrfValid($request)) {
            return $this->renderError(__('Wrong data'));
        }

        $command = new BanUserCommand(
            term: $request->bodyInt('term'),
            timeval: $request->bodyInt('timeval'),
            time: $request->bodyInt('time'),
            reason: $request->body('reason', ''),
            banref: $request->bodyInt('banref'),
        );

        try {
            $this->banUserUseCase->execute($command, $target);
        } catch (BanException $e) {
            return $this->renderResult($e->getMessage(), 'alert-danger', '/profile/' . $id);
        }

        return $this->renderResult(__('User banned'), 'alert-success', '/profile/' . $id);
    }

    public function cancelForm(int $id, int $banId): ViewResponse
    {
        if ($error = $this->staffGuard()) {
            return $error;
        }

        try {
            $this->cancelBanUseCase->getCancelableBan($id, $banId);
        } catch (ProfileNotFoundException) {
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

    public function cancel(Request $request, int $id, int $banId): ViewResponse
    {
        if ($error = $this->staffGuard()) {
            return $error;
        }
        if (! $this->isCsrfValid($request)) {
            return $this->renderError(__('Wrong data'));
        }

        try {
            $ban = $this->cancelBanUseCase->getCancelableBan($id, $banId);
        } catch (ProfileNotFoundException) {
            return $this->renderError(__('Wrong data'));
        } catch (BanException $e) {
            return $this->renderError($e->getMessage());
        }

        $this->cancelBanUseCase->cancel($ban);

        return $this->renderResult(__('Ban terminated'), 'alert-success', '/profile/' . $id . '/bans', __('Ban termination'));
    }

    public function deleteForm(int $id, int $banId): ViewResponse
    {
        if ($error = $this->supervisorGuard()) {
            return $error;
        }

        try {
            $this->deleteBanUseCase->getBan($id, $banId);
        } catch (ProfileNotFoundException) {
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

    public function delete(Request $request, int $id, int $banId): ViewResponse
    {
        if ($error = $this->supervisorGuard()) {
            return $error;
        }
        if (! $this->isCsrfValid($request)) {
            return $this->renderError(__('Wrong data'));
        }

        try {
            $ban = $this->deleteBanUseCase->getBan($id, $banId);
        } catch (ProfileNotFoundException) {
            return $this->renderError(__('Wrong data'));
        }

        $this->deleteBanUseCase->delete($ban);

        return $this->renderResult(__('Ban deleted'), 'alert-success', '/profile/' . $id . '/bans', __('Delete ban'));
    }

    public function clearForm(int $id): ViewResponse
    {
        if ($error = $this->supervisorGuard(__('Violations history can be cleared by Supervisor only'))) {
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

    public function clear(Request $request, int $id): ViewResponse
    {
        if ($error = $this->supervisorGuard(__('Violations history can be cleared by Supervisor only'))) {
            return $error;
        }
        if (! $this->isCsrfValid($request)) {
            return $this->renderError(__('Wrong data'));
        }

        $this->clearBanHistoryUseCase->execute($id);

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

    private function renderForm(Request $request, User $target): ViewResponse
    {
        $title = __('Ban the User');

        $this->navChain->add(__('User Profile'), '/profile/' . $target->id);
        $this->navChain->add($title);

        return new ViewResponse(
            '@profile/public/ban.twig',
            [
                'field_height' => $this->currentUser->config->fieldHeight,
                'title'        => $title,
                'page_title'   => $title,
                'form_action'  => '/profile/' . $target->id . '/bans/new',
                'post_id'      => $request->queryInt('fid'),
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

    private function staffGuard(): ?ViewResponse
    {
        if ($this->currentUser->rights < 7) {
            return $this->renderError(__('Wrong data'), 403);
        }

        return null;
    }

    private function supervisorGuard(?string $message = null): ?ViewResponse
    {
        if ($this->currentUser->rights !== 9) {
            return $this->renderError($message ?? __('Wrong data'), 403);
        }

        return null;
    }

    private function isCsrfValid(Request $request): bool
    {
        $validator = new Validator(
            ['csrf_token' => $request->body('csrf_token', '')],
            ['csrf_token' => ['Csrf']]
        );

        return $validator->isValid();
    }
}
