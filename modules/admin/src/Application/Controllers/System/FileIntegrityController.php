<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\System;

use Johncms\Http\Session;
use Johncms\Modules\Admin\Application\UseCases\CreateFileIntegritySnapshotUseCase;
use Johncms\Modules\Admin\Application\UseCases\ScanFileIntegrityUseCase;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Twig\Markup;

final readonly class FileIntegrityController
{
    private const URL = '/admin/file-integrity';

    public function __construct(
        private NavChain $navChain,
        private ScanFileIntegrityUseCase $scanFileIntegrity,
        private CreateFileIntegritySnapshotUseCase $createSnapshotUseCase,
        private Session $session,
    ) {
    }

    public function index(): ViewResponse
    {
        $title = __('Anti-Spyware');
        $this->navChain->add($title, self::URL);

        return new ViewResponse('@admin/file-integrity.twig', $this->menu($title) + [
            'scan_url'     => self::URL . '/scan',
            'snapshot_url' => self::URL . '/snapshot',
        ]);
    }

    public function scan(): ViewResponse
    {
        $result = $this->scanFileIntegrity->execute();
        $title = __('Snapshot scan');
        $this->navChain->add(__('Anti-Spyware'), self::URL);
        $this->navChain->add($title);

        if (! $result->snapshotExists) {
            return new ViewResponse('@admin/pages/result.twig', $this->menu($title) + [
                'type'          => 'alert-danger',
                'message'       => __('Snapshot is not created'),
                'back_url'      => self::URL . '/snapshot',
                'back_url_name' => __('Create snapshot'),
            ]);
        }

        if ($result->changedFiles === []) {
            return new ViewResponse('@admin/pages/result.twig', $this->menu($title) + [
                'type'     => 'alert-success',
                'message'  => new Markup(__('Excellent!<br>All files are consistent with previously made image.'), 'UTF-8'),
                'back_url' => self::URL,
            ]);
        }

        return new ViewResponse('@admin/file-integrity-scan-result.twig', $this->menu($title) + [
            'bad_files' => $result->changedFiles,
            'back_url'  => self::URL,
        ]);
    }

    public function snapshotConfirm(): ViewResponse
    {
        $title = __('Create snapshot');
        $this->navChain->add(__('Anti-Spyware'), self::URL);
        $this->navChain->add($title);

        return new ViewResponse('@admin/file-integrity-snapshot-confirm.twig', $this->menu($title) + [
            'form_action' => self::URL . '/snapshot',
            'back_url'    => self::URL,
        ]);
    }

    public function createSnapshot(): string
    {
        $this->createSnapshotUseCase->execute();
        $this->session->flash('success_message', __('Snapshot successfully created'));

        redirect(self::URL);
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
