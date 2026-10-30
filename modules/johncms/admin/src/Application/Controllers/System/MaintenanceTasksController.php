<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\System;

use Johncms\AdminTasks\AdminTaskBusyException;
use Johncms\AdminTasks\AdminTaskRegistry;
use Johncms\AdminTasks\AdminTaskGroup;
use Johncms\AdminTasks\AdminTaskStatus;
use Johncms\Modules\Admin\Application\DTO\MaintenanceTaskDTO;
use Johncms\Http\Session;
use Johncms\Modules\Admin\Application\Exceptions\MaintenanceTaskNotFoundException;
use Johncms\Modules\Admin\Application\UseCases\GetMaintenanceTasksUseCase;
use Johncms\Modules\Admin\Application\UseCases\QueueMaintenanceTaskUseCase;
use Johncms\Modules\Admin\Application\UseCases\RunMaintenanceTaskUseCase;
use Johncms\Http\View\ViewResponse;
use Johncms\NavChain;
use Johncms\Http\Request;

final readonly class MaintenanceTasksController
{
    private const URL = '/admin/maintenance';

    public function __construct(
        private NavChain $navChain,
        private AdminTaskRegistry $registry,
        private GetMaintenanceTasksUseCase $getTasks,
        private RunMaintenanceTaskUseCase $runTask,
        private QueueMaintenanceTaskUseCase $queueTask,
        private Session $session,
    ) {
    }

    public function index(): ViewResponse
    {
        return $this->renderList();
    }

    public function run(Request $request): ViewResponse
    {
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
                'groups'          => $this->groups($this->rows($this->getTasks->execute())),
                'form_action'     => self::URL . '/run',
                'error_message'   => $errorMessage,
                'success_message' => (string) $this->session->getFlash('success_message'),
            ]
        );
    }

    /**
     * The tasks under the group they belong to, in the order the groups are wanted; a group with
     * no task is left out.
     *
     * @param list<array<string, mixed>> $rows
     * @return list<array{key: string, title: string, hint: string, tasks: list<array<string, mixed>>}>
     */
    private function groups(array $rows): array
    {
        $groups = [
            AdminTaskGroup::Routine->value   => [__('Routine'), __('Cleaning up and the caches. Safe to run at any time.')],
            AdminTaskGroup::Update->value    => [__('After an update'), __('Run them in this order once the files of the site have been updated.')],
            AdminTaskGroup::Migration->value => [__('One-time: coming from 9.9'), __('Only a site moved from version 9.9 needs them. Run them in this order.')],
        ];

        $result = [];
        foreach ($groups as $key => [$title, $hint]) {
            $tasks = array_values(array_filter($rows, static fn (array $row): bool => $row['group'] === $key));
            if ($tasks !== []) {
                $result[] = ['key' => $key, 'title' => $title, 'hint' => $hint, 'tasks' => $tasks];
            }
        }

        return $result;
    }

    /**
     * @param list<MaintenanceTaskDTO> $tasks
     * @return list<array<string, mixed>>
     */
    private function rows(array $tasks): array
    {
        $labels = [
            'queued'  => ['label' => __('Queued'), 'class' => 'bg-info-subtle text-info-emphasis'],
            'running' => ['label' => __('Running'), 'class' => 'bg-warning-subtle text-warning-emphasis'],
            'done'    => ['label' => __('Completed'), 'class' => 'bg-success-subtle text-success-emphasis'],
            'failed'  => ['label' => __('Failed'), 'class' => 'bg-danger-subtle text-danger-emphasis'],
        ];

        return array_map(
            static fn (MaintenanceTaskDTO $task): array => [
                'command_name' => $task->commandName,
                'title'        => $task->title,
                'description'  => (string) $task->description,
                'background'   => $task->background,
                'group'        => $task->group,
                'status_label' => $task->status === null ? '' : ($labels[$task->status]['label'] ?? $task->status),
                'status_class' => $labels[$task->status ?? '']['class'] ?? 'bg-secondary-subtle text-secondary-emphasis',
                'pending'      => $task->isPending(),
                'output'       => $task->output,
                'finished_at'  => (string) $task->finishedAt,
            ],
            $tasks
        );
    }
}
