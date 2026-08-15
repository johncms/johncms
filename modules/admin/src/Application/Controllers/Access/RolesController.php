<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Access;

use Johncms\Auth\Authorization\RoleRepositoryInterface;
use Johncms\Auth\Authorization\SystemRole;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Admin\Application\DTO\PermissionGroupDTO;
use Johncms\Modules\Admin\Application\DTO\PermissionItemDTO;
use Johncms\Modules\Admin\Application\DTO\RoleEditorDTO;
use Johncms\Modules\Admin\Application\DTO\RoleFormDTO;
use Johncms\Modules\Admin\Application\Exceptions\RoleAccessDeniedException;
use Johncms\Modules\Admin\Application\Exceptions\RoleNotFoundException;
use Johncms\Modules\Admin\Application\Exceptions\RoleSlugTakenException;
use Johncms\Modules\Admin\Application\Exceptions\SystemRoleNotDeletableException;
use Johncms\Modules\Admin\Application\UseCases\DeleteRoleUseCase;
use Johncms\Modules\Admin\Application\UseCases\EnsureRoleManagementAccessUseCase;
use Johncms\Modules\Admin\Application\UseCases\GetRoleEditorUseCase;
use Johncms\Modules\Admin\Application\UseCases\GetRoleListUseCase;
use Johncms\Modules\Admin\Application\UseCases\SaveRoleUseCase;
use Johncms\NavChain;
use Johncms\Validator\Rules\Between;
use Johncms\Validator\Rules\StringLength;
use Johncms\Validator\ValidationResult;
use Johncms\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Roles and what each of them may do.
 *
 * This screen replaces guessing at a number: instead of `rights >= 7`, a role carries the
 * permission keys the code checks, and the site decides which role carries which.
 */
final readonly class RolesController
{
    private const URL = '/admin/roles';

    /** Lower case, digits and hyphens, starting with a letter: it appears in code and in URLs. */
    private const SLUG_PATTERN = '/^[a-z][a-z0-9-]{1,63}$/';

    /** Where a new role starts: above a plain user, well below the moderators. */
    private const DEFAULT_LEVEL = 20;

    public function __construct(
        private NavChain $navChain,
        private Session $session,
        private ValidatorInterface $validator,
        private RoleRepositoryInterface $roles,
        private EnsureRoleManagementAccessUseCase $ensureAccess,
        private GetRoleListUseCase $getRoleList,
        private GetRoleEditorUseCase $getRoleEditor,
        private SaveRoleUseCase $saveRole,
        private DeleteRoleUseCase $deleteRole,
    ) {
    }

    public function index(): ViewResponse
    {
        try {
            $this->ensureAccess->execute();
        } catch (RoleAccessDeniedException $e) {
            return $this->forbidden($e->getMessage());
        }

        $title = __('Roles');
        $this->navChain->add($title, self::URL);

        return new ViewResponse('@admin/roles.twig', $this->menu($title) + [
            'items'           => $this->getRoleList->execute($this->ensureAccess->maxLevel()),
            'add_url'         => self::URL . '/new',
            'success_message' => (string) $this->session->getFlash('success_message'),
        ]);
    }

    public function newForm(): ViewResponse
    {
        try {
            $this->ensureAccess->execute();
            $editor = $this->getRoleEditor->execute(null);
        } catch (RoleAccessDeniedException $e) {
            return $this->forbidden($e->getMessage());
        }

        return $this->renderForm($editor, $this->initialFields(null));
    }

    public function editForm(int $id): ViewResponse
    {
        try {
            $editor = $this->getRoleEditor->execute($id);
            $this->ensureAccess->execute($editor->role);
        } catch (RoleAccessDeniedException $e) {
            return $this->forbidden($e->getMessage());
        } catch (RoleNotFoundException) {
            redirect(self::URL);
        }

        return $this->renderForm($editor, $this->initialFields($editor));
    }

    public function store(Request $request): ViewResponse
    {
        return $this->save($request, null);
    }

    public function update(Request $request, int $id): ViewResponse
    {
        return $this->save($request, $id);
    }

    public function deleteConfirm(int $id): ViewResponse
    {
        $role = $this->roles->findById($id);
        if ($role === null) {
            redirect(self::URL);
        }

        try {
            $this->ensureAccess->execute($role);
        } catch (RoleAccessDeniedException $e) {
            return $this->forbidden($e->getMessage());
        }

        if ($role->is_system) {
            return $this->error(__('A built-in role cannot be deleted'));
        }

        $title = __('Delete:') . ' ' . $role->display_name;
        $this->navChain->add(__('Roles'), self::URL);
        $this->navChain->add($title);

        return new ViewResponse('@admin/role-delete-confirm.twig', $this->menu($title) + [
            'role_name'   => $role->display_name,
            'holders'     => $this->roles->holderCounts(time())[$role->id] ?? 0,
            'form_action' => self::URL . '/' . $role->id . '/delete',
            'back_url'    => self::URL,
        ]);
    }

    public function delete(int $id): ViewResponse
    {
        $role = $this->roles->findById($id);
        if ($role === null) {
            redirect(self::URL);
        }

        try {
            $this->ensureAccess->execute($role);
            $this->deleteRole->execute($role->id);
        } catch (RoleAccessDeniedException $e) {
            return $this->forbidden($e->getMessage());
        } catch (SystemRoleNotDeletableException) {
            return $this->error(__('A built-in role cannot be deleted'));
        } catch (RoleNotFoundException) {
            redirect(self::URL);
        }

        $this->session->flash('success_message', __('The role is deleted'));
        redirect(self::URL);
    }

    private function save(Request $request, ?int $id): ViewResponse
    {
        $fields = $this->fieldsFromRequest($request);

        try {
            $editor = $this->getRoleEditor->execute($id);
            $isSystem = $editor->role->is_system ?? false;
            // A built-in role posts neither name nor level, so there is no new level to check.
            $this->ensureAccess->execute($editor->role, $isSystem ? null : $fields['level']);
        } catch (RoleAccessDeniedException $e) {
            return $this->forbidden($e->getMessage());
        } catch (RoleNotFoundException) {
            redirect(self::URL);
        }

        $result = $this->validate($fields, $isSystem, $id === null);

        if ($result->isValid()) {
            try {
                $this->saveRole->execute(
                    new RoleFormDTO(
                        id: $id,
                        slug: $fields['slug'],
                        name: $fields['name'],
                        level: $fields['level'],
                        permissions: $fields['permissions'],
                    )
                );

                $this->session->flash('success_message', __('Changes saved successfully'));
                redirect(self::URL);
            } catch (RoleSlugTakenException) {
                $result = $result->withError('slug', __('A role with this identifier already exists'));
            } catch (RoleNotFoundException) {
                redirect(self::URL);
            }
        }

        return $this->renderForm($editor, $fields, $result->getErrors());
    }

    /**
     * @param array<string, mixed> $fields
     */
    private function validate(array $fields, bool $isSystem, bool $isNew): ValidationResult
    {
        if ($isSystem) {
            // Only the permission matrix is editable, and it needs no rules of its own: keys the
            // catalogue does not declare are dropped when the role is stored.
            return new ValidationResult();
        }

        $result = $this->validator->validate(
            ['name' => $fields['name'], 'level' => $fields['level']],
            [
                'name'  => [new StringLength(min: 2, max: 191)],
                'level' => [new Between(min: 0, max: $this->ensureAccess->maxLevel())],
            ]
        );

        if ($isNew && preg_match(self::SLUG_PATTERN, $fields['slug']) !== 1) {
            $result = $result->withError(
                'slug',
                __('The identifier may hold lower case letters, digits and hyphens, and starts with a letter')
            );
        }

        return $result;
    }

    /**
     * @param array<string, mixed>            $fields
     * @param array<string, array<int, string>> $errors
     */
    private function renderForm(RoleEditorDTO $editor, array $fields, array $errors = []): ViewResponse
    {
        $role = $editor->role;
        $title = $role === null ? __('New role') : __('Role:') . ' ' . $role->display_name;

        $this->navChain->add(__('Roles'), self::URL);
        $this->navChain->add($title);

        return new ViewResponse('@admin/role-form.twig', $this->menu($title) + [
            'form_action' => $role === null ? self::URL . '/new' : self::URL . '/' . $role->id . '/edit',
            'back_url'    => self::URL,
            'is_new'      => $role === null,
            // A built-in role keeps its name and its place in the hierarchy: code refers to both.
            'is_system'   => $role->is_system ?? false,
            // Allowed everything by SuperAdminVoter, so a matrix would only be a screen of
            // checkboxes nothing reads.
            'full_access' => SystemRole::grantsEverything($role->level ?? 0),
            'role_name'   => $role->display_name ?? '',
            'role_slug'   => $role->slug ?? '',
            'fields'      => $fields,
            'max_level'   => $this->ensureAccess->maxLevel(),
            'groups'      => $this->groupsForView($editor, $errors === [] ? null : $fields['permissions']),
            'undeclared'  => $editor->undeclared,
            'errors'      => $errors,
        ]);
    }

    /**
     * The matrix as the template reads it. After a failed save the ticks come from the form
     * rather than from the database, so nothing the visitor did is lost.
     *
     * @param list<string>|null $checked
     * @return list<array<string, mixed>>
     */
    private function groupsForView(RoleEditorDTO $editor, ?array $checked): array
    {
        return array_map(
            static fn (PermissionGroupDTO $group): array => [
                'label' => $group->label,
                'items' => array_map(
                    static fn (PermissionItemDTO $item): array => [
                        'key'     => $item->key,
                        'label'   => $item->label,
                        'granted' => $checked === null ? $item->granted : in_array($item->key, $checked, true),
                    ],
                    $group->items
                ),
            ],
            $editor->groups
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function initialFields(?RoleEditorDTO $editor): array
    {
        $role = $editor?->role;

        return [
            'slug'        => $role->slug ?? '',
            'name'        => $role->name ?? '',
            'level'       => $role->level ?? self::DEFAULT_LEVEL,
            'permissions' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fieldsFromRequest(Request $request): array
    {
        return [
            'slug'        => mb_strtolower(trim($request->body('slug', ''))),
            'name'        => trim($request->body('name', '')),
            'level'       => $request->bodyInt('level'),
            'permissions' => array_values(array_filter($request->bodyList('permissions'), 'is_string')),
        ];
    }

    private function error(string $message): ViewResponse
    {
        return new ViewResponse('@admin/pages/result.twig', $this->menu(__('Roles')) + [
            'type'     => 'alert-danger',
            'message'  => $message,
            'back_url' => self::URL,
        ]);
    }

    private function forbidden(string $message): ViewResponse
    {
        return new ViewResponse(
            '@admin/pages/result.twig',
            $this->menu(__('Roles')) + [
                'type'     => 'alert-danger',
                'message'  => $message,
                'back_url' => '/admin',
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
            'usr_menu'   => ['roles' => true],
        ];
    }
}
