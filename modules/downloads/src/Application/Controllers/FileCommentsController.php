<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Controllers;

use Johncms\Comments;
use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\PageMeta;
use Johncms\Modules\Downloads\Application\Services\CategoryNavService;
use Johncms\Modules\Downloads\Application\Services\DownloadFilePathService;
use Johncms\Modules\Downloads\Application\Exceptions\FileNotFoundException;
use Johncms\Modules\Downloads\Domain\Repository\DownloadFileRepositoryInterface;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class FileCommentsController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private User $currentUser,
        private DownloadFileRepositoryInterface $fileRepository,
        private CategoryNavService $categoryNavService,
        private DownloadFilePathService $filePathService,
    ) {
        $this->controllerContext->initModule('downloads');
    }

    public function __invoke(int $id): string
    {
        $config = config('johncms');

        if (! $config['mod_down_comm'] && $this->currentUser->rights < 7) {
            http_response_code(403);
            return $this->render->render(
                'system::pages/result',
                [
                    'title'         => __('Comments'),
                    'type'          => 'alert-danger',
                    'message'       => __('Comments are disabled'),
                    'back_url'      => '/downloads/',
                    'back_url_name' => __('Downloads'),
                ]
            );
        }

        try {
            $file = $this->fileRepository->findFile($id);
            if ($file === null) {
                throw new FileNotFoundException();
            }
        } catch (FileNotFoundException) {
            http_response_code(404);
            return $this->render->render(
                'system::pages/result',
                [
                    'title'         => __('File not found'),
                    'type'          => 'alert-danger',
                    'message'       => __('File not found'),
                    'back_url'      => '/downloads/',
                    'back_url_name' => __('Downloads'),
                ]
            );
        }

        if (! is_file($file->dir . '/' . $file->name)) {
            http_response_code(404);
            return $this->render->render(
                'system::pages/result',
                [
                    'title'         => __('File not found'),
                    'type'          => 'alert-danger',
                    'message'       => __('File not found'),
                    'back_url'      => '/downloads/',
                    'back_url_name' => __('Downloads'),
                ]
            );
        }

        if ($file->type === 3 && $this->currentUser->rights < 6 && $this->currentUser->rights !== 4) {
            http_response_code(403);
            return $this->render->render(
                'system::pages/result',
                [
                    'title'         => __('The file is awaiting moderation'),
                    'type'          => 'alert-danger',
                    'message'       => __('The file is awaiting moderation'),
                    'back_url'      => '/downloads/',
                    'back_url_name' => __('Downloads'),
                ]
            );
        }

        // Breadcrumbs
        $this->navChain->add(__('Downloads'), '/downloads/');
        $this->categoryNavService->buildForFileDir($file->dir);
        $this->navChain->add(htmlspecialchars($file->rus_name), $this->filePathService->getFileUrl($file));
        $this->navChain->add(__('Comments'));

        $shortName = mb_strlen($file->rus_name) > 30
            ? mb_substr($file->rus_name, 0, 30) . '...'
            : $file->rus_name;
        $documentTitle = htmlspecialchars($shortName) . ' — ' . __('Comments') . ' — ' . __('Downloads');

        // Set globals required by the legacy Comments class
        global $mod, $start;
        $mod = $this->request->getQuery('mod', '');
        $page = max(1, (int) $this->request->getQuery('page', 1));
        $start = isset($_REQUEST['page'])
            ? ($page - 1) * (int) $this->currentUser->config->kmess
            : (isset($_GET['start']) ? abs((int) $_GET['start']) : 0);

        $meta = new PageMeta($documentTitle, $page);

        // Comments renders a complete page (including layout) internally, so capture and return as-is.
        ob_start();
        new Comments([
            'object_comm_count'   => 'total',
            'comments_table'      => 'download__comments',
            'object_table'        => 'download__files',
            'script'              => '/downloads/comments/' . $id,
            'sub_id'              => $id,
            'owner'               => false,
            'owner_delete'        => false,
            'owner_reply'         => false,
            'owner_edit'          => false,
            'title'               => $meta->title,
            'page_title'          => __('Comments'),
            'templates_namespace' => 'system',
            'back_url'            => $this->filePathService->getFileUrl($file),
        ]);
        return (string) ob_get_clean();
    }
}
