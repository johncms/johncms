<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
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
use Johncms\System\View\Render;
use Johncms\Users\User;
use Johncms\Validator\Validator;

final readonly class BanController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
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
        $this->controllerContext->initModule('profile');
    }

    public function history(int $id): string
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

    public function createForm(int $id): string
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

    public function create(int $id): string
    {
        try {
            $target = $this->getBanFormContextUseCase->execute($id);
        } catch (ProfileNotFoundException $e) {
            return $this->renderError($e->getMessage());
        } catch (ProfileAccessForbiddenException $e) {
            return $this->renderError($e->getMessage(), 403);
        }

        if (! $this->isCsrfValid()) {
            return $this->renderError(__('Wrong data'));
        }

        $command = new BanUserCommand(
            term: $this->request->bodyInt('term'),
            timeval: $this->request->bodyInt('timeval'),
            time: $this->request->bodyInt('time'),
            reason: $this->request->body('reason', ''),
            banref: $this->request->bodyInt('banref'),
        );

        try {
            $this->banUserUseCase->execute($command, $target);
        } catch (BanException $e) {
            return $this->renderResult($e->getMessage(), 'alert-danger', '/profile/' . $id);
        }

        return $this->renderResult(__('User banned'), 'alert-success', '/profile/' . $id);
    }

    public function cancelForm(int $id, int $banId): string
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

    public function cancel(int $id, int $banId): string
    {
        if ($error = $this->staffGuard()) {
            return $error;
        }
        if (! $this->isCsrfValid()) {
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

    public function deleteForm(int $id, int $banId): string
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

    public function delete(int $id, int $banId): string
    {
        if ($error = $this->supervisorGuard()) {
            return $error;
        }
        if (! $this->isCsrfValid()) {
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

    public function clearForm(int $id): string
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

    public function clear(int $id): string
    {
        if ($error = $this->supervisorGuard(__('Violations history can be cleared by Supervisor only'))) {
            return $error;
        }
        if (! $this->isCsrfValid()) {
            return $this->renderError(__('Wrong data'));
        }

        $this->clearBanHistoryUseCase->execute($id);

        return $this->renderResult(__('Violations history cleared'), 'alert-success', '/profile/' . $id . '/bans', __('Violations history'));
    }

    private function renderHistory(int $profileId, BanHistoryDTO $dto, Pagination $pagination): string
    {
        $title = __('Violations History');

        $this->navChain->add(__('User Profile'), '/profile/' . $profileId);
        $this->navChain->add($title);

        $this->render->addData([
            'title'      => $title,
            'page_title' => $title,
        ]);

        $paginationHtml = $pagination->render();

        return $this->render->render(
            'profile::ban_history',
            [
                'title'      => $title,
                'page_title' => $title,
                'pagination' => $paginationHtml,
                'data'       => [
                    'user_name'         => $dto->userName,
                    'items'             => $dto->items,
                    'total'             => $pagination->getTotal(),
                    'pagination'        => $paginationHtml,
                    'clear_history_url' => $dto->clearHistoryUrl,
                    'back_url'          => $dto->backUrl,
                ],
            ]
        );
    }

    private function renderForm(User $target): string
    {
        $title = __('Ban the User');

        $this->navChain->add(__('User Profile'), '/profile/' . $target->id);
        $this->navChain->add($title);

        $this->render->addData([
            'title'      => $title,
            'page_title' => $title,
        ]);

        return $this->render->render(
            'profile::ban',
            [
                'title'      => $title,
                'page_title' => $title,
                'data'       => [
                    'form_action' => '/profile/' . $target->id . '/bans/new',
                    'post_id'     => $this->request->queryInt('fid'),
                    'back_url'    => '/profile/' . $target->id,
                    'user_login'  => $target->name,
                ],
            ]
        );
    }

    private function renderConfirm(string $title, string $message, string $submitName, string $formAction, string $backUrl): string
    {
        $this->navChain->add($title);

        $this->render->addData([
            'title'      => $title,
            'page_title' => $title,
        ]);

        return $this->render->render(
            'profile::ban_cancel',
            [
                'title'      => $title,
                'page_title' => $title,
                'data'       => [
                    'message'     => $message,
                    'submit_name' => $submitName,
                    'form_action' => $formAction,
                    'back_url'    => $backUrl,
                ],
            ]
        );
    }

    private function renderResult(string $message, string $type, string $backUrl, ?string $title = null): string
    {
        return $this->render->render(
            'system::pages/result',
            [
                'title'    => $title ?? __('Ban the User'),
                'type'     => $type,
                'message'  => $message,
                'back_url' => $backUrl,
            ]
        );
    }

    private function renderError(string $message, int $statusCode = 200): string
    {
        if ($statusCode !== 200) {
            http_response_code($statusCode);
        }

        return $this->render->render(
            'system::pages/result',
            [
                'title'   => __('Ban the User'),
                'type'    => 'alert-danger',
                'message' => $message,
            ]
        );
    }

    private function staffGuard(): ?string
    {
        if ($this->currentUser->rights < 7) {
            return $this->renderError(__('Wrong data'), 403);
        }

        return null;
    }

    private function supervisorGuard(?string $message = null): ?string
    {
        if ($this->currentUser->rights !== 9) {
            return $this->renderError($message ?? __('Wrong data'), 403);
        }

        return null;
    }

    private function isCsrfValid(): bool
    {
        $validator = new Validator(
            ['csrf_token' => $this->request->body('csrf_token', '')],
            ['csrf_token' => ['Csrf']]
        );

        return $validator->isValid();
    }
}
