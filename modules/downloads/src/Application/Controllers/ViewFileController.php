<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Controllers;

use Johncms\Modules\Downloads\Application\FilePresenter;
use Johncms\Modules\Downloads\Application\Services\CategoryNavService;
use Johncms\Modules\Downloads\Application\Services\DownloadCategoryPathService;
use Johncms\Modules\Downloads\Application\Services\DownloadFilePathService;
use Johncms\Modules\Downloads\Domain\Models\DownloadCategory;
use Johncms\Modules\Downloads\Domain\Services\ScreenService;
use Johncms\Http\PageMeta;
use Johncms\Modules\Downloads\Application\Exceptions\FileNotFoundException;
use Johncms\Modules\Downloads\Application\Services\FileMediaInfoService;
use Johncms\Modules\Downloads\Application\UseCases\ToggleBookmarkUseCase;
use Johncms\Modules\Downloads\Application\UseCases\ViewFileUseCase;
use Johncms\Modules\Downloads\Application\UseCases\VoteOnFileUseCase;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\Response;

final readonly class ViewFileController
{
    public function __construct(
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private User $currentUser,
        private Session $session,
        private ViewFileUseCase $viewFileUseCase,
        private VoteOnFileUseCase $voteUseCase,
        private ToggleBookmarkUseCase $bookmarkUseCase,
        private FileMediaInfoService $mediaInfoService,
        private FilePresenter $filePresenter,
        private CategoryNavService $categoryNavService,
        private DownloadFilePathService $filePathService,
        private DownloadCategoryPathService $categoryPathService,
    ) {
    }

    public function __invoke(string $filePath): Response
    {
        $parsed = $this->filePathService->parseFilePath('/downloads/' . ltrim($filePath, '/'));
        if ($parsed === null) {
            pageNotFound();
        }

        $id = $parsed['fileId'];

        try {
            $result = $this->viewFileUseCase->execute($id);
        } catch (FileNotFoundException) {
            return new Response(
                $this->render->render(
                    'system::pages/result',
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

        $file = $result->file;
        $canonicalFileSlug = $this->filePathService->getFileSlug($file);
        if ($canonicalFileSlug !== $parsed['fileSlug']) {
            pageNotFound();
        }

        if ((int) $file->refid === 0) {
            if ($parsed['categoryPath'] !== '') {
                pageNotFound();
            }
        } else {
            $file->loadMissing('category');
            if ($file->category !== null) {
                $canonicalCatPath = $this->categoryPathService->getCategoryPath($file->category);
                if ($canonicalCatPath !== $parsed['categoryPath']) {
                    pageNotFound();
                }
            }
        }

        if (! is_file($file->dir . '/' . $file->name)) {
            return new Response(
                $this->render->render(
                    'system::pages/result',
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

        if ($file->type === 3 && $this->currentUser->rights < 6 && $this->currentUser->rights !== 4) {
            return new Response(
                $this->render->render(
                    'system::pages/result',
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

        // Voting (session state is an HTTP concern, stays here)
        $sessionIndex = 'rate_file_' . $id;
        $hasVoteAction = $this->request->query->has('plus') || $this->request->query->has('minus');
        $isPlus = $this->request->query->has('plus');

        $vote = $this->voteUseCase->execute(
            $id,
            $isPlus,
            ! $this->currentUser->isValid() || $this->session->has($sessionIndex) || ! $hasVoteAction
        );

        if ($vote->wasAccepted) {
            $this->session->set($sessionIndex, true);
        }

        // Bookmarks
        $bookmarkAction = null;
        if ($this->currentUser->isValid()) {
            if ($this->request->query->has('addBookmark')) {
                $bookmarkAction = 'add';
            } elseif ($this->request->query->has('delBookmark')) {
                $bookmarkAction = 'remove';
            }
        }

        $inBookmarks = $this->currentUser->isValid()
            ? $this->bookmarkUseCase->execute($id, $this->currentUser->id, $bookmarkAction)
            : 0;

        // Breadcrumbs
        $this->navChain->add(__('Downloads'), '/downloads/');
        if ($file->category !== null) {
            $this->buildCategoryNavChain($file->category);
        } else {
            $this->categoryNavService->buildForFileDir($file->dir);
        }
        $this->navChain->add($file->rus_name);

        // File display data
        $extension = strtolower(pathinfo($file->name, PATHINFO_EXTENSION));
        $mediaInfo = $this->mediaInfoService->build(
            $file->dir . '/' . $file->name,
            $extension,
            ScreenService::getScreens($id)
        );

        $fileData = array_merge($file->toArray(), [
            'file_type'        => $mediaInfo->fileType,
            'file_properties'  => $mediaInfo->fileProperties,
            'screenshots'      => $mediaInfo->screenshots,
            'image_info'       => $mediaInfo->imageInfo,
            'description'      => $file->about_html,
            'upload_user'      => $result->uploadUser
                ? ['id' => $result->uploadUser->id, 'name' => $result->uploadUser->name]
                : ['id' => 0, 'name' => ''],
            'can_vote'         => ! $this->session->has($sessionIndex) && $this->currentUser->isValid(),
            'vote_accepted'    => $vote->wasAccepted,
            'rate'             => [$vote->plus, $vote->minus],
            'main_file'        => $this->buildDownloadLink($file->dir, $file->name, $file->rus_name, $file->size, $id),
            'additional_files' => [],
        ]);

        foreach ($result->additionalFiles as $moreFile) {
            $fileData['additional_files'][] = $this->buildDownloadLink(
                $file->dir,
                $moreFile->name,
                $moreFile->rus_name,
                $moreFile->size ?? null,
                $id,
                $moreFile->id
            );
        }

        $pageTitle = htmlspecialchars($file->rus_name);
        $meta = new PageMeta($pageTitle . ' — ' . __('Downloads'), 1);
        $this->render->addData([
            'title'       => $meta->title,
            'page_title'  => $pageTitle,
            'description' => $meta->description,
        ]);

        $fileUrl = $this->filePathService->getFileUrl($file);

        return new Response($this->render->render(
            'downloads::view',
            [
                'id'           => $id,
                'file_url'     => $fileUrl,
                'file'         => $fileData,
                'in_bookmarks' => $inBookmarks,
                'urls'         => [
                    'downloads' => '/downloads/',
                    'back'      => $file->category !== null
                        ? $this->categoryPathService->getCategoryUrl($file->category)
                        : '/downloads/',
                ],
            ]
        ));
    }

    private function buildCategoryNavChain(DownloadCategory $category): void
    {
        $ancestors = [];
        $current = $category;
        while ($current->refid > 0) {
            $parent = DownloadCategory::query()->find($current->refid);
            if ($parent === null) {
                break;
            }
            $ancestors[] = $parent;
            $current = $parent;
        }
        foreach (array_reverse($ancestors) as $ancestor) {
            $this->navChain->add(htmlspecialchars($ancestor->rus_name), $this->categoryPathService->getCategoryUrl($ancestor));
        }
        $this->navChain->add(htmlspecialchars($category->rus_name), $this->categoryPathService->getCategoryUrl($category));
    }

    private function buildDownloadLink(
        string $dir,
        string $name,
        string $displayName,
        ?int $size,
        int $fileId,
        ?int $moreId = null
    ): array {
        $fsPath = $dir . '/' . $name;
        $moreLink = $moreId !== null ? '?more=' . $moreId : '';
        return [
            'source_url' => '/' . $fsPath,
            'url'        => '/downloads/load/' . $fileId . '/' . $moreLink,
            'name'       => $displayName,
            'size'       => FilePresenter::formatFileSize($size ?? (is_file($fsPath) ? filesize($fsPath) : 0)),
        ];
    }
}
