<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Controllers;

use Johncms\Auth\CurrentUser;
use Johncms\FileInfo;
use Johncms\Modules\Downloads\Application\Services\DownloadCategoryPathService;
use Johncms\Modules\Downloads\Domain\Models\DownloadBookmark;
use Johncms\Modules\Downloads\Domain\Models\DownloadCategory;
use Johncms\Modules\Downloads\Domain\Models\DownloadComment;
use Johncms\Modules\Downloads\Domain\Models\DownloadFile;
use Johncms\Modules\Downloads\Domain\Models\DownloadMoreFile;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\HttpFoundation\Response;

final readonly class ScanDirectoryController
{
    public function __construct(
        private NavChain $navChain,
        private CurrentUser $currentUser,
        private DownloadCategoryPathService $categoryPathService,
    ) {
    }

    public function __invoke(Request $request): ViewResponse
    {
        set_time_limit(99999);

        $id = max(0, $request->queryInt('id', 0));
        $do = $request->queryParam('do') ?? '';

        $this->navChain->add(__('Downloads'), '/downloads/');
        $this->navChain->add(__('Update'));

        if ($do === 'clean') {
            return $this->handleClean($id);
        }

        return $this->handleScan($request, $id);
    }

    private function handleClean(int $id): ViewResponse
    {
        $seenRealFiles = [];
        DownloadFile::query()->orderBy('id')->each(function (DownloadFile $file) use (&$seenRealFiles): void {
            $realFile = realpath($file->dir . '/' . $file->name);
            $isDuplicate = $realFile !== false && isset($seenRealFiles[$realFile]);

            if ($realFile === false || ! file_exists($realFile) || $isDuplicate) {
                DownloadMoreFile::query()->where('refid', $file->id)->each(function (DownloadMoreFile $more) use ($file): void {
                    @unlink($file->dir . '/' . $more->name);
                });
                DownloadMoreFile::query()->where('refid', $file->id)->delete();
                DownloadBookmark::query()->where('file_id', $file->id)->delete();
                DownloadComment::query()->where('sub_id', $file->id)->delete();
                $file->delete();
                return;
            }

            $seenRealFiles[$realFile] = true;
        });

        DownloadMoreFile::query()->each(function (DownloadMoreFile $more): void {
            $parentFile = DownloadFile::query()->find($more->refid);
            if ($parentFile === null) {
                $more->delete();
                return;
            }
            $realFile = realpath($parentFile->dir . '/' . $more->name);
            if ($realFile === false || ! file_exists($realFile)) {
                $more->delete();
            }
        });

        $seenRealDirs = [];
        DownloadCategory::query()->orderBy('id')->each(function (DownloadCategory $category) use (&$seenRealDirs): void {
            $realDir = realpath($category->dir);
            $isDuplicate = $realDir !== false && isset($seenRealDirs[$realDir]);

            if ($realDir === false || ! is_dir($realDir) || $isDuplicate) {
                $this->deleteCategoryFiles($category->id);
                $category->delete();
                return;
            }

            $seenRealDirs[$realDir] = true;
        });

        DownloadCategory::query()->each(function (DownloadCategory $category): void {
            $count = DownloadFile::query()->where('type', 2)->where('dir', 'like', $category->dir . '%')->count();
            $category->update(['total' => $count]);
        });

        $backUrl = $id > 0
            ? ($this->categoryPathService->getCategoryUrlById($id) ?? '/downloads/')
            : '/downloads/';

        return new ViewResponse('@theme/pages/result.twig', [
            'title'         => __('Remove missing files'),
            'type'          => 'alert-success',
            'message'       => __('Database successfully updated'),
            'back_url'      => $backUrl,
            'back_url_name' => __('Back'),
        ]);
    }

    private function deleteCategoryFiles(int $categoryId): void
    {
        $fileIds = DownloadFile::query()->where('refid', $categoryId)->pluck('id')->all();
        if (! empty($fileIds)) {
            DownloadBookmark::query()->whereIn('file_id', $fileIds)->delete();
            DownloadComment::query()->whereIn('sub_id', $fileIds)->delete();
            DownloadMoreFile::query()->whereIn('refid', $fileIds)->delete();
        }
        DownloadFile::query()->where('refid', $categoryId)->delete();
    }

    private function handleScan(Request $request, int $id): ViewResponse
    {
        $yes = $request->query->has('yes');
        $mod = $request->queryInt('mod', 0);

        if ($id > 0) {
            $category = DownloadCategory::query()->find($id);
            if ($category === null || ! is_dir($category->dir)) {
                return new ViewResponse('@theme/pages/result.twig', [
                    'title'         => __('Error'),
                    'type'          => 'alert-danger',
                    'message'       => __('The directory does not exist'),
                    'back_url'      => '/downloads/',
                    'back_url_name' => __('Downloads'),
                ], Response::HTTP_NOT_FOUND);
            }
            $scanDir = $category->dir;
        } else {
            $scanDir = 'upload/downloads/files';
        }

        $updatedInfo = [];
        $selectMode = true;

        if ($yes) {
            $updatedInfo = $this->scan($scanDir, $mod);
            $selectMode = false;
        }

        $backUrl = isset($category)
            ? $this->categoryPathService->getCategoryUrl($category)
            : '/downloads/';

        return new ViewResponse('@downloads/public/scan-dir.twig', [
            'title'          => __('Update'),
            'page_title'     => __('Update'),
            'id'             => $id,
            'urls'           => ['downloads' => '/downloads/'],
            'updated_info'   => $updatedInfo,
            'select_mode'    => $selectMode,
        ]);
    }

    private function scan(string $scanDir, int $mod): array
    {
        $knownPaths = [];
        $pathToId = [];
        $knownMoreFiles = [];

        DownloadFile::query()->each(function (DownloadFile $file) use (&$knownPaths, &$pathToId): void {
            $path = $file->dir . '/' . $file->name;
            $knownPaths[] = $path;
            $pathToId[$path] = $file->id;
        });

        DownloadCategory::query()->each(function (DownloadCategory $cat) use (&$knownPaths, &$pathToId): void {
            $knownPaths[] = $cat->dir;
            $pathToId[$cat->dir] = $cat->id;
        });

        DownloadMoreFile::query()->pluck('name')->each(function (string $name) use (&$knownMoreFiles): void {
            $knownMoreFiles[] = $name;
        });

        $ignoredFiles = ['name.dat', 'index.php'];
        $rawPaths = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($scanDir),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $fullPath => $splObject) {
            $filename = $splObject->getFilename();
            if (in_array($filename, $ignoredFiles, true) || str_starts_with($filename, '.')) {
                continue;
            }
            $rawPaths[] = $fullPath;
        }

        $addedCategories = 0;
        $addedFiles = 0;
        $addedMoreFiles = 0;

        if (empty($rawPaths)) {
            return ['categories' => 0, 'files' => 0, 'additional_files' => 0];
        }

        asort($rawPaths);
        $cleanPaths = array_map(static function (string $path): string {
            return (new FileInfo($path))->getCleanPath();
        }, $rawPaths);
        asort($cleanPaths);

        $oldDirs = [];
        $sort = time();

        foreach ($rawPaths as $key => $rawPath) {
            $cleanPath = $cleanPaths[$key];

            if ($cleanPath !== $rawPath) {
                if (is_dir($rawPath) && ! is_dir($cleanPath)) {
                    mkdir($cleanPath);
                    $oldDirs[] = $rawPath;
                } elseif (! file_exists($cleanPath)) {
                    if (copy($rawPath, $cleanPath)) {
                        unlink($rawPath);
                    }
                }
            }

            if (in_array($cleanPath, $knownPaths, true)) {
                continue;
            }

            $origParts = explode(DIRECTORY_SEPARATOR, $rawPath);

            if (is_dir($cleanPath)) {
                $name = basename($cleanPath);
                $origName = array_pop($origParts);
                $dir = dirname($cleanPath);
                $refid = (int) ($pathToId[$dir] ?? 0);

                $category = DownloadCategory::query()->create([
                    'refid'    => $refid,
                    'dir'      => $dir . '/' . $name,
                    'sort'     => $sort++,
                    'name'     => $name,
                    'field'    => 0,
                    'rus_name' => $origName,
                    'text'     => '',
                    'desc'     => '',
                ]);

                $pathToId[$dir . '/' . $name] = $category->id;
                $addedCategories++;
            } else {
                $name = basename($cleanPath);
                $origName = array_pop($origParts);

                if (preg_match('/^file(\d+)_/', $name)) {
                    if (! in_array($name, $knownMoreFiles, true)) {
                        $refid = (int) str_replace('file', '', explode('_', $name)[0]);
                        $linkName = mb_substr(
                            str_replace('file' . $refid . '_', __('Download') . ' ', $name),
                            0,
                            200
                        );

                        DownloadMoreFile::query()->create([
                            'refid'    => $refid,
                            'time'     => time(),
                            'name'     => $name,
                            'rus_name' => $linkName,
                            'size'     => filesize($cleanPath),
                        ]);

                        $addedMoreFiles++;
                    }
                } else {
                    $dir = dirname($cleanPath);
                    $refid = (int) ($pathToId[$dir] ?? 0);

                    $file = DownloadFile::query()->create([
                        'refid'    => $refid,
                        'dir'      => $dir,
                        'time'     => time(),
                        'name'     => $name,
                        'text'     => 'Download',
                        'rus_name' => $origName,
                        'type'     => 2,
                        'user_id'  => $this->currentUser->id(),
                        'about'    => '',
                        'desc'     => '',
                    ]);

                    if ($mod) {
                        $screensPath = \UPLOAD_PATH . 'downloads' . \DS . 'screen' . \DS;
                        $screenFile = null;
                        foreach (['.jpg', '.gif', '.png'] as $ext) {
                            if (is_file($cleanPath . $ext)) {
                                $screenFile = $cleanPath . $ext;
                                break;
                            }
                        }

                        if ($screenFile && mkdir($screensPath . $file->id, 0777, true)) {
                            @chmod($screensPath . $file->id, 0777);
                            @copy($screenFile, $screensPath . $file->id . '/' . str_replace($cleanPath, (string) $file->id, $screenFile));
                            unlink($screenFile);
                        }

                        if (is_file($cleanPath . '.txt')) {
                            @copy($cleanPath . '.txt', \UPLOAD_PATH . 'downloads' . \DS . 'about' . \DS . $file->id . '.txt');
                            unlink($cleanPath . '.txt');
                        }
                    }

                    $addedFiles++;
                }
            }
        }

        if (! empty($oldDirs)) {
            arsort($oldDirs);
            array_map('rmdir', $oldDirs);
        }

        return [
            'categories'       => $addedCategories,
            'files'            => $addedFiles,
            'additional_files' => $addedMoreFiles,
        ];
    }
}
