<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\System;

use Johncms\AdminTasks\AdminTaskBusyException;
use Johncms\AdminTasks\AdminTaskRegistry;
use Johncms\AdminTasks\AdminTaskStatus;
use Johncms\Http\Controller\AdminControllerContext;
use Johncms\Modules\Admin\Application\DTO\MaintenanceTaskDTO;
use Johncms\Http\Session;
use Johncms\Modules\Admin\Application\Exceptions\MaintenanceTaskNotFoundException;
use Johncms\Modules\Admin\Application\UseCases\GetMaintenanceTasksUseCase;
use Johncms\Modules\Admin\Application\UseCases\QueueMaintenanceTaskUseCase;
use Johncms\Modules\Admin\Application\UseCases\RunMaintenanceTaskUseCase;
use Johncms\Http\View\ViewResponse;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Validator\Validator;

final readonly class MaintenanceTasksController
{
    private const URL = '/admin/maintenance';

    public function __construct(
        private AdminControllerContext $controllerContext,
        private NavChain $navChain,
        private AdminTaskRegistry $registry,
        private GetMaintenanceTasksUseCase $getTasks,
        private RunMaintenanceTaskUseCase $runTask,
        private QueueMaintenanceTaskUseCase $queueTask,
        private Session $session,
    ) {
        $this->controllerContext->initModule('admin');
    }

    public function index(): ViewResponse
    {
        return $this->renderList();
    }

    public function run(Request $request): ViewResponse
    {
        if (! $this->isCsrfValid($request)) {
            return $this->renderList(__('Wrong data'));
        }

        $commandName = $request->body('command', '');
        $task = $this->registry->find($commandName);
        if ($task === null) {
            return $this->renderList(__('Wrong data'));
        }

        try {
            if ($task->background) {
                $this->queueTask->execute($commandName);
                $this->session->flash('success_message', __('The task has been queued and will start within a minute'));
            } else {
                $state = $this->runTask->execute($commandName);
                $this->session->flash('success_message', $state?->status === AdminTaskStatus::Done
                    ? __('The task has been completed')
                    : __('The task has failed'));
            }
        } catch (MaintenanceTaskNotFoundException) {
            return $this->renderList(__('Wrong data'));
        } catch (AdminTaskBusyException) {
            return $this->renderList(__('The task is already running'));
        }

        redirect(self::URL);
    }

    private function isCsrfValid(Request $request): bool
    {
        $validator = new Validator(
            ['csrf_token' => $request->body('csrf_token', '')],
            ['csrf_token' => ['Csrf']]
        );

        return $validator->isValid();
    }

    private function renderList(string $errorMessage = ''): ViewResponse
    {
        $title = __('Maintenance');
        $this->navChain->add($title);

        return new ViewResponse(
            '@admin/maintenance.twig',
            [
                'title'           => $title,
                'page_title'      => $title,
                'sys_menu'        => ['maintenance' => true],
                'tasks'           => $this->rows($this->getTasks->execute()),
                'form_action'     => self::URL . '/run',
                'error_message'   => $errorMessage,
                'success_message' => (string) $this->session->getFlash('success_message'),
            ]
        );
    }

    /**
     * @param list<MaintenanceTaskDTO> $tasks
     * @return list<array<string, mixed>>
     */
    private function rows(array $tasks): array
    {
        $labels = [
            'queued'  => ['label' => __('Queued'), 'class' => 'bg-info'],
            'running' => ['label' => __('Running'), 'class' => 'bg-warning text-dark'],
            'done'    => ['label' => __('Completed'), 'class' => 'bg-success'],
            'failed'  => ['label' => __('Failed'), 'class' => 'bg-danger'],
        ];

        return array_map(
            static fn (MaintenanceTaskDTO $task): array => [
                'command_name' => $task->commandName,
                'title'        => $task->title,
                'description'  => (string) $task->description,
                'background'   => $task->background,
                'status_label' => $task->status === null ? '' : ($labels[$task->status]['label'] ?? $task->status),
                'status_class' => $labels[$task->status ?? '']['class'] ?? 'bg-secondary',
                'pending'      => $task->isPending(),
                'output'       => $task->output,
                'finished_at'  => (string) $task->finishedAt,
            ],
            $tasks
        );
    }
}
