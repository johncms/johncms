<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Modules;

use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Admin\Application\Exceptions\MaintenanceTaskNotFoundException;
use Johncms\Modules\Admin\Application\Services\ModuleRowMapper;
use Johncms\Modules\Admin\Application\UseCases\GetModulesOverviewUseCase;
use Johncms\Modules\Admin\Application\UseCases\QueueMaintenanceTaskUseCase;
use Johncms\Modules\ModuleInstallService;
use Johncms\Modules\ModuleOperationResult;
use Johncms\Modules\ModuleRegistry;
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
        private ModuleRowMapper $rowMapper,
    ) {
    }

    public function index(Request $request): ViewResponse
    {
        $title = __('Modules');
        $this->navChain->add($title);

        $overview = $this->overview->execute();
        $groups = [
            'installed' => __('Installed modules'),
            'available' => __('New modules'),
            'problems'  => __('Need attention'),
        ];
        $tab = $request->queryParam('tab');
        if (! isset($groups[$tab]) || ($tab !== 'installed' && $overview[$tab] === [])) {
            $tab = 'installed';
        }

        $tabs = [];
        foreach ($groups as $group => $groupTitle) {
            // A group with nothing in it is left out; the installed ones are always there.
            if ($group !== 'installed' && $overview[$group] === []) {
                continue;
            }

            $tabs[] = [
                'title'  => $groupTitle,
                'url'    => self::URL . ($group === 'installed' ? '' : '?tab=' . $group),
                'active' => $tab === $group,
                'count'  => count($overview[$group]),
                'tone'   => $group === 'problems' ? 'danger' : '',
            ];
        }

        return new ViewResponse('@admin/modules.twig', [
            'title'           => $title,
            'page_title'      => $title,
            'module_menu'     => ['modules' => true],
            'tab'             => $tab,
            'tabs'            => $tabs,
            'rows'            => array_map($this->rowMapper->row(...), $overview[$tab]),
            'has_available'   => $overview['available'] !== [],
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

        $dialog = [
            'title'      => $this->operationTitle($operation),
            'module'     => $this->rowMapper->row($state),
            'operation'  => $operation,
            'action_url' => self::URL . '/run',
            'back_url'   => self::URL,
        ];

        if ($request->isXmlHttpRequest()) {
            return new ViewResponse('@admin/modules-confirm-dialog.twig', $dialog);
        }

        $this->navChain->add(__('Modules'), self::URL);
        $this->navChain->add($dialog['title']);

        return new ViewResponse('@admin/modules-confirm.twig', [
            'title'       => $dialog['title'],
            'module_menu' => ['modules' => true],
            'dialog'      => $dialog,
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
}
