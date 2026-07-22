<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Controllers;

use Johncms\FileInfo;
use Johncms\Modules\Downloads\Application\FilePresenter;
use Johncms\Modules\Downloads\Application\Services\CategoryNavService;
use Johncms\Modules\Downloads\Application\Services\DownloadFilePathService;
use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Downloads\Domain\Models\DownloadFile;
use Johncms\Modules\Downloads\Domain\Models\DownloadMoreFile;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\Http\Session;
use Johncms\System\View\Render;
use Johncms\Utils\DateFormatterInterface;

final readonly class AdditionalFilesController
{
    private const DEFAULT_EXTENSIONS = [
        'mp4', 'rar', 'zip', 'pdf', 'nth', 'txt', 'tar', 'gz',
        'jpg', 'jpeg', 'gif', 'png', 'bmp', '3gp', 'mp3', 'mpg',
        'thm', 'jad', 'jar', 'cab', 'sis', 'sisx', 'exe', 'msi',
        'apk', 'djvu', 'fb2', 'webm', 'avi', 'mov', 'aac', 'm4a',
    ];

    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private Session $session,
        private NavChain $navChain,
        private DateFormatterInterface $dateFormatter,
        private CategoryNavService $categoryNavService,
        private DownloadFilePathService $filePathService,
    ) {
        $this->controllerContext->initModule('downloads');
    }

    public function __invoke(int $id): string
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
        $this->navChain->add(htmlspecialchars($file->rus_name), $this->filePathService->getFileUrl($file));
        $this->navChain->add(__('Additional files'));

        $baseUrl = '/downloads/additional-files/' . $id . '/';

        $editId = $this->request->getQuery('edit') !== null ? (int) $this->request->getQuery('edit') : null;
        $delId = $this->request->getQuery('del') !== null ? (int) $this->request->getQuery('del') : null;

        if ($editId !== null) {
            return $this->handleEdit($id, $file, $editId, $baseUrl);
        }

        if ($delId !== null) {
            return $this->handleDelete($id, $file, $delId, $baseUrl);
        }

        if ($this->request->getMethod() === 'POST') {
            return $this->handleUpload($id, $file, $baseUrl);
        }

        return $this->showList($id, $file, $baseUrl);
    }

    private function showList(int $id, DownloadFile $file, string $baseUrl): string
    {
        $pageTitle = htmlspecialchars($file->rus_name);
        $this->render->addData([
            'title'      => $pageTitle,
            'page_title' => $pageTitle,
        ]);

        $additionalFiles = DownloadMoreFile::query()->where('refid', $id)->get()->map(function (DownloadMoreFile $more) use ($id, $baseUrl): array {
            return [
                'id'           => $more->id,
                'name'         => $more->name,
                'rus_name'     => htmlspecialchars($more->rus_name),
                'display_date' => $this->dateFormatter->format($more->time),
                'display_size' => FilePresenter::formatFileSize($more->size),
                'edit_url'     => $baseUrl . '?edit=' . $more->id,
                'delete_url'   => $baseUrl . '?del=' . $more->id,
            ];
        })->all();

        return $this->render->render('downloads::files_more', [
            'id'               => $id,
            'additional_files' => $additionalFiles,
            'action_url'       => $baseUrl,
            'extensions'       => implode(', ', self::DEFAULT_EXTENSIONS),
            'file_url'         => $this->filePathService->getFileUrl($file),
        ]);
    }

    private function handleEdit(int $id, DownloadFile $file, int $editId, string $baseUrl): string
    {
        $moreFile = DownloadMoreFile::query()->find($editId);

        if ($moreFile === null) {
            return $this->notFound();
        }

        if ($this->request->getMethod() === 'POST') {
            $post = $this->request->getParsedBody();
            $nameLink = isset($post['name_link']) ? htmlspecialchars(mb_substr($post['name_link'], 0, 200)) : null;

            if ($nameLink) {
                $moreFile->update(['rus_name' => $nameLink]);
                http_response_code(302);
                header('Location: ' . $baseUrl);
                exit;
            }
        }

        $pageTitle = htmlspecialchars($file->rus_name);
        $this->render->addData([
            'title'      => __('Edit File'),
            'page_title' => $pageTitle,
        ]);

        return $this->render->render('downloads::edit_additional_form', [
            'id'         => $id,
            'file_name'  => htmlspecialchars($moreFile->rus_name),
            'action_url' => $baseUrl . '?edit=' . $editId,
            'back_url'   => $baseUrl,
        ]);
    }

    private function handleDelete(int $id, DownloadFile $file, int $delId, string $baseUrl): string
    {
        $moreFile = DownloadMoreFile::query()->find($delId);

        if ($moreFile === null) {
            return $this->notFound();
        }

        $hasYes = $this->request->getQuery('yes') !== null;
        if ($hasYes && $this->request->getMethod() === 'POST') {
            $post = $this->request->getParsedBody();
            $sessionToken = $this->session->get('delete_token');

            if (isset($post['delete_token']) && $sessionToken !== null && $sessionToken === $post['delete_token']) {
                if (is_file($file->dir . '/' . $moreFile->name)) {
                    unlink($file->dir . '/' . $moreFile->name);
                }
                $moreFile->delete();
            }

            http_response_code(302);
            header('Location: ' . $baseUrl);
            exit;
        }

        $deleteToken = uniqid('', true);
        $this->session->set('delete_token', $deleteToken);

        $pageTitle = htmlspecialchars($file->rus_name);
        $this->render->addData([
            'title'      => __('Delete File'),
            'page_title' => $pageTitle,
        ]);

        return $this->render->render('downloads::delete_additional', [
            'id'           => $id,
            'delete_token' => $deleteToken,
            'action_url'   => $baseUrl . '?del=' . $delId . '&yes',
            'back_url'     => $baseUrl,
        ]);
    }

    private function handleUpload(int $id, DownloadFile $file, string $baseUrl): string
    {
        $config = config('johncms');
        $post = $this->request->getParsedBody();
        $files = $this->request->getUploadedFiles();
        $errors = [];

        $linkFile = isset($post['link_file']) ? str_replace('./', '_', trim($post['link_file'])) : null;

        /** @var \GuzzleHttp\Psr7\UploadedFile|null $uploadedFile */
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
            $fname = $uploadedFile->getClientFilename();
            $fsize = $uploadedFile->getSize();
        }

        if (! $doFile) {
            $errors[] = __('File not attached');
        }

        if ($doFile && empty($errors)) {
            $newFileName = isset($post['new_file']) ? trim($post['new_file']) : null;
            $nameLink = isset($post['name_link']) ? htmlspecialchars(mb_substr($post['name_link'], 0, 200)) : null;

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
                $errors[] = __('Prohibited file type!<br>To upload allowed files that have the following extensions') . ': ' . implode(', ', self::DEFAULT_EXTENSIONS);
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
                    $uploadedFile->moveTo($file->dir . '/' . $newFname);
                    $moved = $uploadedFile->isMoved();
                }

                if ($moved) {
                    DownloadMoreFile::query()->create([
                        'refid'    => $id,
                        'time'     => time(),
                        'name'     => $newFname,
                        'rus_name' => $nameLink,
                        'size'     => (int) $fsize,
                    ]);

                    return $this->render->render('system::pages/result', [
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

        return $this->render->render('system::pages/result', [
            'title'         => __('Error'),
            'type'          => 'alert-danger',
            'message'       => $errors,
            'back_url'      => $baseUrl,
            'back_url_name' => __('Repeat'),
        ]);
    }

    private function notFound(): string
    {
        http_response_code(404);
        return $this->render->render('system::pages/result', [
            'title'         => __('File not found'),
            'type'          => 'alert-danger',
            'message'       => __('File not found'),
            'back_url'      => '/downloads/',
            'back_url_name' => __('Downloads'),
        ]);
    }
}
