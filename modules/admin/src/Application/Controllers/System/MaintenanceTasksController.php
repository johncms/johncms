<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\System;

use Johncms\AdminTasks\AdminTaskBusyException;
use Johncms\AdminTasks\AdminTaskRegistry;
use Johncms\AdminTasks\AdminTaskStatus;
use Johncms\Http\Controller\AdminControllerContext;
use Johncms\Http\Session;
use Johncms\Modules\Admin\Application\Exceptions\MaintenanceTaskNotFoundException;
use Johncms\Modules\Admin\Application\UseCases\GetMaintenanceTasksUseCase;
use Johncms\Modules\Admin\Application\UseCases\QueueMaintenanceTaskUseCase;
use Johncms\Modules\Admin\Application\UseCases\RunMaintenanceTaskUseCase;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\System\View\Render;
use Johncms\Validator\Validator;

final readonly class MaintenanceTasksController
{
    private const URL = '/admin/maintenance';

    public function __construct(
        private AdminControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private AdminTaskRegistry $registry,
        private GetMaintenanceTasksUseCase $getTasks,
        private RunMaintenanceTaskUseCase $runTask,
        private QueueMaintenanceTaskUseCase $queueTask,
        private Session $session,
    ) {
        $this->controllerContext->initModule('admin');
    }

    public function index(): string
    {
        return $this->renderList();
    }

    public function run(Request $request): string
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

    private function renderList(?string $errorMessage = null): string
    {
        $title = __('Maintenance');
        $this->navChain->add($title);

        $successMessage = $this->session->getFlash('success_message');

        $this->render->addData(
            [
                'title'      => $title,
                'page_title' => $title,
                'sys_menu'   => ['maintenance' => true],
            ]
        );

        return $this->render->render(
            'admin::maintenance_tasks',
            [
                'tasks'           => $this->getTasks->execute(),
                'form_action'     => self::URL . '/run',
                'error_message'   => $errorMessage,
                'success_message' => $successMessage,
            ]
        );
    }
}
