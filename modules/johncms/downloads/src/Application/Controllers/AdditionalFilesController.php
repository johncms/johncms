<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Controllers;

use Johncms\FileInfo;
use Johncms\Modules\Downloads\Application\FilePresenter;
use Johncms\Modules\Downloads\Application\Services\CategoryNavService;
use Johncms\Modules\Downloads\Application\Services\DownloadFilePathService;
use Johncms\Modules\Downloads\Domain\Models\DownloadFile;
use Johncms\Modules\Downloads\Domain\Models\DownloadMoreFile;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\View\ViewResponse;
use Johncms\Utils\DateFormatterInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Twig\Markup;

final readonly class AdditionalFilesController
{
    private const DEFAULT_EXTENSIONS = [
        'mp4', 'rar', 'zip', 'pdf', 'nth', 'txt', 'tar', 'gz',
        'jpg', 'jpeg', 'gif', 'png', 'bmp', '3gp', 'mp3', 'mpg',
        'thm', 'jad', 'jar', 'cab', 'sis', 'sisx', 'exe', 'msi',
        'apk', 'djvu', 'fb2', 'webm', 'avi', 'mov', 'aac', 'm4a',
    ];

    public function __construct(
        private Session $session,
        private NavChain $navChain,
        private DateFormatterInterface $dateFormatter,
        private CategoryNavService $categoryNavService,
        private DownloadFilePathService $filePathService,
    ) {
    }

    public function __invoke(Request $request, int $id): RedirectResponse|ViewResponse
    {
        $file = DownloadFile::query()
            ->where('id', $id)
            ->whereIn('type', [2, 3])
            ->first();

        if ($file === null || ! is_file($file->dir . '/' . $file->name)) {
            return $this->notFound();
        }

        $this->navChain->add(__('Downloads'), '/downloads/');
        $this->categoryNavService->buildForFileDir($file->dir);
        $this->navChain->add($file->rus_name, $this->filePathService->getFileUrl($file));
        $this->navChain->add(__('Additional files'));

        $baseUrl = '/downloads/additional-files/' . $id . '/';

        $editId = $request->query->has('edit') ? (int) $request->queryParam('edit') : null;
        $delId = $request->query->has('del') ? (int) $request->queryParam('del') : null;

        if ($editId !== null) {
            return $this->handleEdit($request, $id, $file, $editId, $baseUrl);
        }

        if ($delId !== null) {
            return $this->handleDelete($request, $id, $file, $delId, $baseUrl);
        }

        if ($request->getMethod() === 'POST') {
            return $this->handleUpload($request, $id, $file, $baseUrl);
        }

        return $this->showList($id, $file, $baseUrl);
    }

    private function showList(int $id, DownloadFile $file, string $baseUrl): ViewResponse
    {
        $additionalFiles = DownloadMoreFile::query()->where('refid', $id)->get()->map(function (DownloadMoreFile $more) use ($id, $baseUrl): array {
            return [
                'id'           => $more->id,
                'name'         => $more->name,
                'rus_name'     => $more->rus_name,
                'display_date' => $this->dateFormatter->format($more->time),
                'display_size' => FilePresenter::formatFileSize($more->size),
                'edit_url'     => $baseUrl . '?edit=' . $more->id,
                'delete_url'   => $baseUrl . '?del=' . $more->id,
            ];
        })->all();

        return new ViewResponse('@downloads/public/additional-files.twig', [
            'title'            => $file->rus_name,
            'page_title'       => $file->rus_name,
            'additional_files' => $additionalFiles,
            'action_url'       => $baseUrl,
            'extensions'       => implode(', ', self::DEFAULT_EXTENSIONS),
            'file_url'         => $this->filePathService->getFileUrl($file),
        ]);
    }

    private function handleEdit(Request $request, int $id, DownloadFile $file, int $editId, string $baseUrl): RedirectResponse|ViewResponse
    {
        $moreFile = DownloadMoreFile::query()->find($editId);

        if ($moreFile === null) {
            return $this->notFound();
        }

        if ($request->getMethod() === 'POST') {
            $post = $request->request->all();
            $nameLink = isset($post['name_link']) ? mb_substr($post['name_link'], 0, 200) : null;

            if ($nameLink) {
                $moreFile->update(['rus_name' => $nameLink]);

                return new RedirectResponse($baseUrl);
            }
        }

        return new ViewResponse('@downloads/public/edit-additional-file.twig', [
            'title'      => __('Edit File'),
            'page_title' => $file->rus_name,
            'file_name'  => $moreFile->rus_name,
            'action_url' => $baseUrl . '?edit=' . $editId,
            'back_url'   => $baseUrl,
        ]);
    }

    private function handleDelete(Request $request, int $id, DownloadFile $file, int $delId, string $baseUrl): RedirectResponse|ViewResponse
    {
        $moreFile = DownloadMoreFile::query()->find($delId);

        if ($moreFile === null) {
            return $this->notFound();
        }

        $hasYes = $request->query->has('yes');
        if ($hasYes && $request->getMethod() === 'POST') {
            $post = $request->request->all();
            $sessionToken = $this->session->get('delete_token');

            if (isset($post['delete_token']) && $sessionToken !== null && $sessionToken === $post['delete_token']) {
                if (is_file($file->dir . '/' . $moreFile->name)) {
                    unlink($file->dir . '/' . $moreFile->name);
                }
                $moreFile->delete();
            }

            return new RedirectResponse($baseUrl);
        }

        $deleteToken = uniqid('', true);
        $this->session->set('delete_token', $deleteToken);

        return new ViewResponse('@downloads/public/confirm-delete.twig', [
            'title'        => __('Delete File'),
            'page_title'   => $file->rus_name,
            'delete_token' => $deleteToken,
            'action_url'   => $baseUrl . '?del=' . $delId . '&yes',
            'back_url'     => $baseUrl,
        ]);
    }

    private function handleUpload(Request $request, int $id, DownloadFile $file, string $baseUrl): ViewResponse
    {
        $config = config('johncms');
        $post = $request->request->all();
        $files = $request->files->all();
        $errors = [];

        $linkFile = isset($post['link_file']) ? str_replace('./', '_', trim($post['link_file'])) : null;

        /** @var \Symfony\Component\HttpFoundation\File\UploadedFile|null $uploadedFile */
        $uploadedFile = $files['fail'] ?? null;

        $fname = null;
        $fsize = 0;
        $doFile = false;

        if ($linkFile) {
            if (! str_starts_with($linkFile, 'http://')) {
                $errors[] = __('Invalid Link');
            } else {
                $linkFile = str_replace('http://', '', $linkFile);
                if ($linkFile) {
                    $doFile = true;
                    $fname = basename($linkFile);
                } else {
                    $errors[] = __('Invalid Link');
                }
            }
        } elseif ($uploadedFile !== null) {
            $doFile = true;
            $fname = $uploadedFile->getClientOriginalName();
            $fsize = $uploadedFile->getSize();
        }

        if (! $doFile) {
            $errors[] = __('File not attached');
        }

        if ($doFile && empty($errors)) {
            $newFileName = isset($post['new_file']) ? trim($post['new_file']) : null;
            $nameLink = isset($post['name_link']) ? mb_substr($post['name_link'], 0, 200) : null;

            $fileInfo = new FileInfo($fname);
            $ext = strtolower($fileInfo->getExtension());

            if (! empty($newFileName)) {
                $fileInfo = new FileInfo($newFileName . '.' . $ext);
            }

            $fname = $fileInfo->getCleanName();

            if (empty($nameLink)) {
                $errors[] = __('The required fields are not filled');
            }

            if ($fsize > 1024 * $config['flsz'] && ! $linkFile) {
                $errors[] = __('The weight of the file exceeds') . ' ' . $config['flsz'] . 'kb.';
            }

            if (! in_array($ext, self::DEFAULT_EXTENSIONS, true)) {
                $errors[] = new Markup(
                    __('Prohibited file type!<br>To upload allowed files that have the following extensions') . ': ' . implode(', ', self::DEFAULT_EXTENSIONS),
                    'UTF-8'
                );
            }

            if (empty($errors)) {
                $newFname = 'file' . $id . '_' . $fname;
                if (file_exists($file->dir . '/' . $newFname)) {
                    $newFname = 'file' . $id . '_' . time() . $fname;
                }

                if ($linkFile) {
                    $copied = copy('http://' . $linkFile, $file->dir . '/' . $newFname);
                    $fsize = $copied ? filesize($file->dir . '/' . $newFname) : 0;
                    $moved = $copied;
                } else {
                    try {
                        $uploadedFile->move($file->dir, $newFname);
                        $moved = true;
                    } catch (\Symfony\Component\HttpFoundation\File\Exception\FileException) {
                        $moved = false;
                    }
                }

                if ($moved) {
                    DownloadMoreFile::query()->create([
                        'refid'    => $id,
                        'time'     => time(),
                        'name'     => $newFname,
                        'rus_name' => $nameLink,
                        'size'     => (int) $fsize,
                    ]);

                    return new ViewResponse('@theme/pages/result.twig', [
                        'title'         => __('File attached'),
                        'type'          => 'alert-success',
                        'message'       => __('File attached'),
                        'back_url'      => $this->filePathService->getFileUrl($file),
                        'back_url_name' => __('Back'),
                    ]);
                }

                $errors[] = __('File not attached');
            }
        }

        return new ViewResponse('@theme/pages/result.twig', [
            'title'         => __('Error'),
            'type'          => 'alert-danger',
            'message'       => $errors,
            'back_url'      => $baseUrl,
            'back_url_name' => __('Repeat'),
        ]);
    }

    private function notFound(): ViewResponse
    {
        return new ViewResponse('@theme/pages/result.twig', [
            'title'         => __('File not found'),
            'type'          => 'alert-danger',
            'message'       => __('File not found'),
            'back_url'      => '/downloads/',
            'back_url_name' => __('Downloads'),
        ], Response::HTTP_NOT_FOUND);
    }
}
