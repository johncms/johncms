<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Access;

use Johncms\Auth\CurrentUser;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Admin\Application\DTO\UserRolesDTO;
use Johncms\Modules\Admin\Application\Exceptions\RoleAccessDeniedException;
use Johncms\Modules\Admin\Application\Exceptions\UserNotFoundException;
use Johncms\Modules\Admin\Application\UseCases\EnsureRoleManagementAccessUseCase;
use Johncms\Modules\Admin\Application\UseCases\EnsureUserRoleAccessUseCase;
use Johncms\Modules\Admin\Application\UseCases\GetUserRolesUseCase;
use Johncms\Modules\Admin\Application\UseCases\UpdateUserRolesUseCase;
use Johncms\NavChain;
use Johncms\Validator\ValidationResult;
use Symfony\Component\HttpFoundation\Response;

/**
 * The roles of one account: which of them it holds, and until when.
 */
final readonly class UserRolesController
{
    private const URL = '/admin/users';

    public function __construct(
        private NavChain $navChain,
        private Session $session,
        private CurrentUser $currentUser,
        private EnsureRoleManagementAccessUseCase $ensureRoleAccess,
        private EnsureUserRoleAccessUseCase $ensureUserRoleAccess,
        private GetUserRolesUseCase $getUserRoles,
        private UpdateUserRolesUseCase $updateUserRoles,
    ) {
    }

    public function form(int $id): ViewResponse
    {
        try {
            $this->ensureUserRoleAccess->execute($id);
            $context = $this->getUserRoles->execute($id, $this->ensureRoleAccess->maxLevel());
        } catch (RoleAccessDeniedException $e) {
            return $this->forbidden($e->getMessage());
        } catch (UserNotFoundException) {
            redirect(self::URL);
        }

        return $this->render($context);
    }

    public function save(Request $request, int $id): ViewResponse
    {
        try {
            $this->ensureUserRoleAccess->execute($id);
            $viewerLevel = $this->ensureRoleAccess->maxLevel();
        } catch (RoleAccessDeniedException $e) {
            return $this->forbidden($e->getMessage());
        }

        $selected = [];
        $result = new ValidationResult();

        foreach ($this->tickedRoles($request) as $roleId) {
            $raw = trim($request->body('expires_' . $roleId, ''));
            $expiresAt = $raw === '' ? null : $this->endOfDay($raw);

            if ($raw !== '' && $expiresAt === null) {
                $result = $result->withError(ValidationResult::FORM_KEY, __('The expiry date is not a date'));
                break;
            }

            $selected[$roleId] = $expiresAt;
        }

        if (! $result->isValid()) {
            try {
                $context = $this->getUserRoles->execute($id, $viewerLevel);
            } catch (UserNotFoundException) {
                redirect(self::URL);
            }

            return $this->render($context, $result->getErrors());
        }

        try {
            $this->updateUserRoles->execute($id, $selected, $viewerLevel, $this->currentUser->id() ?: null);
        } catch (UserNotFoundException) {
            redirect(self::URL);
        }

        $this->session->flash('success_message', __('Changes saved successfully'));
        redirect(self::URL . '/' . $id . '/roles');
    }

    /**
     * @return list<int>
     */
    private function tickedRoles(Request $request): array
    {
        $ids = [];

        foreach ($request->bodyInts('roles') as $roleId) {
            if (is_int($roleId) && $roleId > 0) {
                $ids[] = $roleId;
            }
        }

        return $ids;
    }

    /**
     * The grant runs out at the end of the day that was picked, not at midnight before it.
     */
    private function endOfDay(string $date): ?int
    {
        $timestamp = strtotime($date . ' 23:59:59');

        return $timestamp === false ? null : $timestamp;
    }

    /**
     * @param array<string, array<int, string>> $errors
     */
    private function render(UserRolesDTO $context, array $errors = []): ViewResponse
    {
        $title = __('Roles of the user');
        $this->navChain->add(__('Users'), self::URL);
        $this->navChain->add($title);

        return new ViewResponse('@admin/user-roles.twig', $this->menu($title) + [
            'user_name'       => $context->userName,
            'user_url'        => '/profile/' . $context->userId,
            'default_role'    => $context->defaultRole,
            'rows'            => $context->rows,
            'form_action'     => self::URL . '/' . $context->userId . '/roles',
            'back_url'        => self::URL,
            'roles_url'       => '/admin/roles',
            'errors'          => $errors[ValidationResult::FORM_KEY] ?? [],
            'success_message' => (string) $this->session->getFlash('success_message'),
        ]);
    }

    private function forbidden(string $message): ViewResponse
    {
        return new ViewResponse(
            '@admin/pages/result.twig',
            $this->menu(__('Roles of the user')) + [
                'type'     => 'alert-danger',
                'message'  => $message,
                'back_url' => self::URL,
            ],
            Response::HTTP_FORBIDDEN
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function menu(string $title): array
    {
        return [
            'title'      => $title,
            'page_title' => $title,
            'usr_menu'   => ['userlist' => true],
        ];
    }
}
