<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Services;

use Johncms\Modules\Admin\Application\DTO\RoleListItemDTO;

/**
 * The rows of the list of roles, as admin::roles draws them: the role, where it stands, what it
 * carries, who holds it, and where editing and deleting it lead for the visitor.
 */
final readonly class RoleListRowMapper
{
    public const PATH = '/admin/roles';

    /**
     * @param list<RoleListItemDTO> $items
     * @return list<array<string, mixed>>
     */
    public function map(array $items): array
    {
        return array_map($this->row(...), $items);
    }

    /**
     * @return array<string, mixed>
     */
    private function row(RoleListItemDTO $item): array
    {
        $url = self::PATH . '/' . $item->id;

        return [
            'id'          => $item->id,
            'name'        => $item->name,
            'slug'        => $item->slug,
            'level'       => $item->level,
            'is_system'   => $item->isSystem,
            'full_access' => $item->fullAccess,
            'permissions' => $item->permissions,
            'holders'     => $this->holders($item),
            'edit_url'    => $item->manageable ? $url . '/edit' : null,
            // A built-in role is referred to by code and stays.
            'delete_url'  => $item->manageable && ! $item->isSystem ? $url . '/delete' : null,
        ];
    }

    /**
     * Who holds the role. The default role and the guest role are not given out, so they have a
     * group of visitors in place of a number.
     *
     * @return array{count: int|null, group: string, url: string|null}
     */
    private function holders(RoleListItemDTO $item): array
    {
        return match (true) {
            $item->isGuest   => ['count' => null, 'group' => __('Guests'), 'url' => null],
            $item->isDefault => ['count' => null, 'group' => __('Everybody signed in'), 'url' => null],
            default          => [
                'count' => $item->holders,
                'group' => '',
                'url'   => $item->holders > 0 ? UserListControlsMapper::PATH . '?role=' . $item->id : null,
            ],
        };
    }
}
