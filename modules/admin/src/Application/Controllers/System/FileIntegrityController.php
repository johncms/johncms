<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\System;

use Johncms\Http\Controller\AdminControllerContext;
use Johncms\Modules\Admin\Application\UseCases\CreateFileIntegritySnapshotUseCase;
use Johncms\Modules\Admin\Application\UseCases\ScanFileIntegrityUseCase;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;
use Johncms\Validator\Validator;

final readonly class FileIntegrityController
{
    private const URL = '/admin/file-integrity';

    public function __construct(
        private AdminControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private ScanFileIntegrityUseCase $scanFileIntegrity,
        private CreateFileIntegritySnapshotUseCase $createSnapshotUseCase,
    ) {
        $this->controllerContext->initModule('admin');
    }

    public function index(): string
    {
        $title = __('Anti-Spyware');
        $this->navChain->add($title, self::URL);
        $this->render->addData($this->menu($title));

        return $this->render->render('admin::antispy', [
            'scan_url'     => self::URL . '/scan',
            'snapshot_url' => self::URL . '/snapshot',
        ]);
    }

    public function scan(): string
    {
        $result = $this->scanFileIntegrity->execute();
        $title = __('Snapshot scan');
        $this->navChain->add(__('Anti-Spyware'), self::URL);
        $this->navChain->add($title);
        $this->render->addData($this->menu($title));

        if (! $result->snapshotExists) {
            return $this->render->render('system::pages/result', [
                'title'         => $title,
                'type'          => 'alert-danger',
                'message'       => __('Snapshot is not created'),
                'back_url'      => self::URL . '/snapshot',
                'back_url_name' => __('Create snapshot'),
            ]);
        }

        if ($result->changedFiles === []) {
            return $this->render->render('system::pages/result', [
                'title'    => $title,
                'type'     => 'alert-success',
                'message'  => __('Excellent!<br>All files are consistent with previously made image.'),
                'back_url' => self::URL,
            ]);
        }

        return $this->render->render('admin::antispy_scan_result', [
            'bad_files' => $result->changedFiles,
            'back_url'  => self::URL,
        ]);
    }

    public function snapshotConfirm(): string
    {
        $title = __('Create snapshot');
        $this->navChain->add(__('Anti-Spyware'), self::URL);
        $this->navChain->add($title);
        $this->render->addData($this->menu($title));

        return $this->render->render('admin::antispy_create_confirm', [
            'form_action' => self::URL . '/snapshot',
            'back_url'    => self::URL,
        ]);
    }

    public function createSnapshot(): string
    {
        if ($this->isCsrfValid()) {
            $this->createSnapshotUseCase->execute();
            $_SESSION['success_message'] = __('Snapshot successfully created');
        }

        redirect(self::URL);
    }

    private function isCsrfValid(): bool
    {
        $validator = new Validator(
            ['csrf_token' => (string) $this->request->getPost('csrf_token', '')],
            ['csrf_token' => ['Csrf']]
        );

        return $validator->isValid();
    }

    /**
     * @return array<string, mixed>
     */
    private function menu(string $title): array
    {
        return [
            'title'      => $title,
            'page_title' => $title,
            'sec_menu'   => ['antispy' => true],
        ];
    }
}
