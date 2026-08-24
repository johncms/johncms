<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Controllers;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\CurrentUser;
use Johncms\Modules\Downloads\Application\Services\DownloadsPermissions;
use Johncms\Comments;
use Johncms\Http\PageMeta;
use Johncms\Modules\Downloads\Application\Services\CategoryNavService;
use Johncms\Modules\Downloads\Application\Services\DownloadFilePathService;
use Johncms\Modules\Downloads\Application\Exceptions\FileNotFoundException;
use Johncms\Modules\Downloads\Domain\Repository\DownloadFileRepositoryInterface;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\View\RendererInterface;
use Symfony\Component\HttpFoundation\Response;

final readonly class FileCommentsController
{
    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private RendererInterface $renderer,
        private NavChain $navChain,
        private CurrentUser $currentUser,
        private DownloadFileRepositoryInterface $fileRepository,
        private CategoryNavService $categoryNavService,
        private DownloadFilePathService $filePathService,
    ) {
    }

    public function __invoke(Request $request, int $id): Response
    {
        $config = config('johncms');

        if (! $config['mod_down_comm'] && ! $this->accessChecker->allows(DownloadsPermissions::COMMENTS_ALWAYS_VIEW)) {
            return new Response(
                $this->renderer->render(
                    '@theme/pages/result.twig',
                    [
                        'title'         => __('Comments'),
                        'type'          => 'alert-danger',
                        'message'       => __('Comments are disabled'),
                        'back_url'      => '/downloads/',
                        'back_url_name' => __('Downloads'),
                    ]
                ),
                Response::HTTP_FORBIDDEN
            );
        }

        try {
            $file = $this->fileRepository->findFile($id);
            if ($file === null) {
                throw new FileNotFoundException();
            }
        } catch (FileNotFoundException) {
            return new Response(
                $this->renderer->render(
                    '@theme/pages/result.twig',
                    [
                        'title'         => __('File not found'),
                        'type'          => 'alert-danger',
                        'message'       => __('File not found'),
                        'back_url'      => '/downloads/',
                        'back_url_name' => __('Downloads'),
                    ]
                ),
                Response::HTTP_NOT_FOUND
            );
        }

        if (! is_file($file->dir . '/' . $file->name)) {
            return new Response(
                $this->renderer->render(
                    '@theme/pages/result.twig',
                    [
                        'title'         => __('File not found'),
                        'type'          => 'alert-danger',
                        'message'       => __('File not found'),
                        'back_url'      => '/downloads/',
                        'back_url_name' => __('Downloads'),
                    ]
                ),
                Response::HTTP_NOT_FOUND
            );
        }

        if ($file->type === 3 && ! $this->accessChecker->allows(DownloadsPermissions::MODERATE)) {
            return new Response(
                $this->renderer->render(
                    '@theme/pages/result.twig',
                    [
                        'title'         => __('The file is awaiting moderation'),
                        'type'          => 'alert-danger',
                        'message'       => __('The file is awaiting moderation'),
                        'back_url'      => '/downloads/',
                        'back_url_name' => __('Downloads'),
                    ]
                ),
                Response::HTTP_FORBIDDEN
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

        $mod = $request->queryParam('mod', '');
        $page = max(1, $request->queryInt('page', 1));
        $start = $request->query->has('page')
            ? ($page - 1) * (int) $this->currentUser->user()->config->kmess
            : abs($request->queryInt('start', 0));

        $meta = new PageMeta($documentTitle, $page);

        // Comments renders a complete page (including layout) internally, so capture and return as-is.
        ob_start();
        new Comments([
            'mod'                 => $mod,
            'start'               => $start,
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
            'back_url'            => $this->filePathService->getFileUrl($file),
        ]);
        return new Response((string) ob_get_clean());
    }
}
