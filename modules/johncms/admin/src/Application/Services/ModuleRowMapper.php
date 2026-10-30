<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Services;

use Johncms\Modules\ModuleState;
use Johncms\Modules\ModuleStatus;

/**
 * The rows of the list of the modules, as admin::modules draws them: what a module is, its state,
 * and the operations it allows — each confirmed in a dialog first.
 */
final readonly class ModuleRowMapper
{
    public const string URL = '/admin/modules';

    /**
     * @return array<string, mixed>
     */
    public function row(ModuleState $state): array
    {
        $status = $state->status;
        $confirm = static fn (string $operation): string => self::URL . '/confirm?' . http_build_query(['module' => $state->key, 'operation' => $operation]);

        // Installing again is what finishes an installation that stopped halfway, so it is
        // offered for both.
        $canInstall = in_array($status, [ModuleStatus::Discovered, ModuleStatus::Installing], true);
        $actions = [];
        if ($status === ModuleStatus::Disabled) {
            $actions[] = $this->action($confirm('enable'), __('Switch on'), 'power');
        }
        if ($status === ModuleStatus::Enabled && ! $state->system) {
            $actions[] = $this->action($confirm('disable'), __('Switch off'), 'power-off');
        }
        if (in_array($status, [ModuleStatus::Enabled, ModuleStatus::Disabled], true)) {
            $actions[] = $this->action($confirm('update'), __('Update'), 'refresh-cw');
        }
        if (in_array($status, [ModuleStatus::Enabled, ModuleStatus::Disabled, ModuleStatus::Installing], true) && ! $state->system) {
            $actions[] = $this->action($confirm('uninstall'), __('Remove'), 'trash-2', danger: true);
        }

        return [
            'key'          => $state->key,
            'alias'        => $state->alias,
            'name'         => $state->name,
            'version'      => $state->version ?? CMS_VERSION,
            'system'       => $state->system,
            'status'       => $status->value,
            'status_label' => self::statusLabel($status),
            'status_tone'  => self::statusTone($status),
            'problem'      => $state->problem,
            'install_url'  => $canInstall ? $confirm('install') : '',
            'actions'      => $actions,
        ];
    }

    public static function statusLabel(ModuleStatus $status): string
    {
        return match ($status) {
            ModuleStatus::Enabled      => __('Switched on'),
            ModuleStatus::Disabled     => __('Switched off'),
            ModuleStatus::Discovered   => __('Not installed'),
            ModuleStatus::Broken       => __('Broken'),
            ModuleStatus::Incompatible => __('Incompatible'),
            ModuleStatus::Installing   => __('Installation unfinished'),
        };
    }

    /**
     * The tone of the badge of a status: success, muted, info, danger or warning.
     */
    public static function statusTone(ModuleStatus $status): string
    {
        return match ($status) {
            ModuleStatus::Enabled      => 'success',
            ModuleStatus::Disabled     => 'muted',
            ModuleStatus::Discovered   => 'info',
            ModuleStatus::Broken       => 'danger',
            ModuleStatus::Incompatible, ModuleStatus::Installing => 'warning',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function action(string $url, string $title, string $icon, bool $danger = false): array
    {
        return ['method' => 'get', 'url' => $url, 'title' => $title, 'icon' => $icon, 'danger' => $danger, 'dialog' => true, 'fields' => []];
    }
}
