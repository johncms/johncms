<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Modules;

use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Admin\Application\Exceptions\MaintenanceTaskNotFoundException;
use Johncms\Modules\Admin\Application\UseCases\GetModulesOverviewUseCase;
use Johncms\Modules\Admin\Application\UseCases\QueueMaintenanceTaskUseCase;
use Johncms\Modules\ModuleInstallService;
use Johncms\Modules\ModuleOperationResult;
use Johncms\Modules\ModuleRegistry;
use Johncms\Modules\ModuleState;
use Johncms\Modules\ModuleStatus;
use Johncms\NavChain;
use Psr\Log\LoggerInterface;

/**
 * The modules of the site: what is installed, what is lying in the directory waiting to be, and
 * what is wrong with the rest.
 *
 * Every operation asks for confirmation first and answers with the steps it took, because
 * installing a module is half a dozen things in a row and the interesting part is which of them
 * failed. Each one is also written to the log: this is the screen through which code arrives on
 * the site.
 */
final readonly class ModulesController
{
    private const string URL = '/admin/modules';

    public function __construct(
        private NavChain $navChain,
        private GetModulesOverviewUseCase $overview,
        private ModuleInstallService $modules,
        private ModuleRegistry $registry,
        private Session $session,
        private LoggerInterface $logger,
        private QueueMaintenanceTaskUseCase $queueTask,
    ) {
    }

    public function index(): ViewResponse
    {
        $title = __('Modules');
        $this->navChain->add($title);

        $overview = $this->overview->execute();

        return new ViewResponse('@admin/modules.twig', [
            'title'           => $title,
            'page_title'      => $title,
            'module_menu'     => ['modules' => true],
            'installed'       => array_map($this->row(...), $overview['installed']),
            'available'       => array_map($this->row(...), $overview['available']),
            'problems'        => array_map($this->row(...), $overview['problems']),
            'action_url'      => self::URL . '/run',
            'success_message' => (string) $this->session->getFlash('success_message'),
            'error_message'   => (string) $this->session->getFlash('error_message'),
            'operation_steps' => (array) $this->session->getFlash('operation_steps'),
        ]);
    }

    /**
     * The confirmation screen. Nothing is done here — it exists so that "remove the forum" is
     * never one stray click, and so that the consequences are spelled out before the click that
     * matters.
     */
    public function confirm(Request $request): ViewResponse
    {
        $key = $request->queryParam('module');
        $operation = $request->queryParam('operation');
        $state = $this->registry->find($key);

        if ($state === null || ! in_array($operation, ['install', 'enable', 'disable', 'update', 'uninstall'], true)) {
            $this->session->flash('error_message', __('Wrong data'));

            redirect(self::URL);
        }

        $title = __('Modules');
        $this->navChain->add($title, self::URL);
        $this->navChain->add($this->operationTitle($operation));

        return new ViewResponse('@admin/modules-confirm.twig', [
            'title'       => $this->operationTitle($operation),
            'page_title'  => $this->operationTitle($operation),
            'module_menu' => ['modules' => true],
            'module'      => $this->row($state),
            'operation'   => $operation,
            'action_url'  => self::URL . '/run',
            'back_url'    => self::URL,
        ]);
    }

    public function run(Request $request): ViewResponse
    {
        $key = $request->body('module');
        $operation = $request->body('operation');
        $withData = $request->body('purge') !== '';
        $withDemo = $request->body('demo') !== '';

        // A module with heavy migrations does not fit into the time a web request is given on a
        // modest host, so the operation can be handed to the scheduler instead.
        if ($request->body('background') !== '' && $this->queue($key, $operation, $withData, $withDemo)) {
            redirect(self::URL);
        }

        $result = match ($operation) {
            'install'   => $this->modules->install($key, $withDemo),
            'enable'    => $this->modules->enable($key),
            'disable'   => $this->modules->disable($key),
            'update'    => $this->modules->update($key),
            'uninstall' => $this->modules->uninstall($key, $withData),
            default     => null,
        };

        if ($result === null) {
            $this->session->flash('error_message', __('Wrong data'));

            redirect(self::URL);
        }

        $this->record($operation, $key, $result);

        $this->session->flash('operation_steps', $result->steps());
        $result->isSuccessful()
            ? $this->session->flash('success_message', __('Done'))
            : $this->session->flash('error_message', (string) $result->error());

        redirect(self::URL);
    }

    /**
     * Hands the operation to the scheduler. Only the ones that can take a while are queued —
     * switching a module on or off is a line in a file and is done here and now.
     */
    private function queue(string $key, string $operation, bool $withData, bool $withDemo): bool
    {
        $command = match ($operation) {
            'install'   => 'module:install',
            'update'    => 'module:update',
            'uninstall' => 'module:uninstall',
            default     => null,
        };

        if ($command === null || $this->registry->find($key) === null) {
            $this->session->flash('error_message', __('Wrong data'));

            return true;
        }

        $arguments = ['module' => $key];
        if ($operation === 'install' && $withDemo) {
            $arguments['--demo'] = true;
        }
        if ($operation === 'uninstall' && $withData) {
            $arguments['--purge'] = true;
        }

        try {
            $this->queueTask->execute($command, $arguments);
        } catch (MaintenanceTaskNotFoundException) {
            $this->session->flash('error_message', __('Wrong data'));

            return true;
        }

        $this->logger->info('Module operation queued.', ['module' => $key, 'operation' => $operation]);
        $this->session->flash(
            'success_message',
            __('The task has been queued and will start within a minute')
        );

        return true;
    }

    private function record(string $operation, string $key, ModuleOperationResult $result): void
    {
        $this->logger->info(
            sprintf('Module %s: %s', $operation, $result->isSuccessful() ? 'done' : 'failed'),
            ['module' => $key, 'operation' => $operation, 'steps' => $result->steps()]
        );
    }

    private function operationTitle(string $operation): string
    {
        return match ($operation) {
            'install'   => __('Install the module'),
            'enable'    => __('Switch the module on'),
            'disable'   => __('Switch the module off'),
            'update'    => __('Update the module'),
            'uninstall' => __('Remove the module'),
            default     => __('Modules'),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function row(ModuleState $state): array
    {
        $labels = [
            ModuleStatus::Enabled->value      => ['label' => __('Switched on'), 'class' => 'bg-success'],
            ModuleStatus::Disabled->value     => ['label' => __('Switched off'), 'class' => 'bg-secondary'],
            ModuleStatus::Discovered->value   => ['label' => __('Not installed'), 'class' => 'bg-info'],
            ModuleStatus::Broken->value       => ['label' => __('Broken'), 'class' => 'bg-danger'],
            ModuleStatus::Incompatible->value => ['label' => __('Incompatible'), 'class' => 'bg-warning text-dark'],
        ];

        return [
            'key'         => $state->key,
            'alias'       => $state->alias,
            'name'        => $state->name,
            'version'     => $state->version ?? CMS_VERSION,
            'system'      => $state->system,
            'status'      => $state->status->value,
            'status_label' => $labels[$state->status->value]['label'],
            'status_class' => $labels[$state->status->value]['class'],
            'problem'     => $state->problem,
            'can_enable'  => $state->status === ModuleStatus::Disabled,
            'can_disable' => $state->status === ModuleStatus::Enabled && ! $state->system,
            'can_install' => $state->status === ModuleStatus::Discovered,
            'can_update'  => in_array($state->status, [ModuleStatus::Enabled, ModuleStatus::Disabled], true),
            'can_remove'  => in_array($state->status, [ModuleStatus::Enabled, ModuleStatus::Disabled], true)
                && ! $state->system,
        ];
    }
}
