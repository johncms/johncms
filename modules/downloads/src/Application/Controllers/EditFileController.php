<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Downloads\Application\Services\CategoryNavService;
use Johncms\Modules\Downloads\Application\Services\DownloadFilePathService;
use Johncms\Modules\Downloads\Application\Services\DownloadSlugService;
use Johncms\Modules\Downloads\Domain\Models\DownloadFile;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;

final readonly class EditFileController
{
    private const AUDIO_EXTENSIONS = ['mp3', 'aac'];
    private const AUDIO_TAG_KEYS = ['artist', 'title', 'album', 'genre', 'year'];

    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private CategoryNavService $categoryNavService,
        private DownloadFilePathService $filePathService,
        private DownloadSlugService $slugService,
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

        $audioTags = $this->readAudioTags($file->dir . '/' . $file->name);

        if ($this->request->getMethod() === 'POST') {
            return $this->handleSave($id, $file, $audioTags);
        }

        $this->render->addData([
            'title'      => __('Edit File'),
            'page_title' => __('Edit File'),
        ]);

        $this->navChain->add(__('Downloads'), '/downloads/');
        $this->categoryNavService->buildForFileDir($file->dir);
        $this->navChain->add(htmlspecialchars($file->rus_name), $this->filePathService->getFileUrl($file));
        $this->navChain->add(__('Edit File'));

        return $this->render->render('downloads::edit_file_form', [
            'id'         => $id,
            'file_data'  => [
                'text'      => htmlspecialchars($file->rus_name),
                'name_link' => htmlspecialchars($file->text),
                'desc'      => htmlspecialchars($file->about, ENT_QUOTES, 'UTF-8'),
            ],
            'audio_tags'  => $audioTags,
            'action_url'  => '/downloads/edit-file/' . $id . '/',
            'file_url'    => $this->filePathService->getFileUrl($file),
        ]);
    }

    private function handleSave(int $id, DownloadFile $file, array $audioTags): string
    {
        $post = $this->request->getParsedBody();
        $name = isset($post['text']) ? trim($post['text']) : null;
        $nameLink = isset($post['name_link']) ? htmlspecialchars(mb_substr($post['name_link'], 0, 200)) : null;
        $desc = isset($post['desc']) ? trim($post['desc']) : null;

        if (! $name || ! $nameLink) {
            return $this->render->render('system::pages/result', [
                'title'         => __('Edit File'),
                'type'          => 'alert-danger',
                'message'       => __('The required fields are not filled'),
                'back_url'      => '/downloads/edit-file/' . $id . '/',
                'back_url_name' => __('Repeat'),
            ]);
        }

        $slug = $this->slugService->generateUniqueFileSlug($name, (int) $file->refid, $id);

        $file->update([
            'rus_name' => $name,
            'slug'     => $slug,
            'text'     => $nameLink,
            'about'    => $desc,
        ]);

        if (! empty($audioTags) && ! empty($post['audio'])) {
            $saveTags = [];
            foreach ($audioTags as $key => $tag) {
                $saveTags[$key][0] = iconv('UTF-8', 'windows-1251', $post['audio'][$key] ?? '');
            }

            $tagsWriter = new \getid3_writetags();
            $tagsWriter->filename = $file->dir . '/' . $file->name;
            $tagsWriter->tagformats = ['id3v1', 'id3v2.3'];
            $tagsWriter->tag_encoding = 'cp1251';
            $tagsWriter->tag_data = $saveTags;
            $tagsWriter->WriteTags();
        }

        $fileUrl = $this->filePathService->getFileUrl($file);
        http_response_code(302);
        header('Location: ' . $fileUrl);
        exit;
    }

    private function readAudioTags(string $filePath): array
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (! in_array($extension, self::AUDIO_EXTENSIONS, true)) {
            return [];
        }

        $getID3 = new \getID3();
        $getID3->encoding = 'cp1251';
        $getid = $getID3->analyze($filePath);

        $tagsArray = $getid['tags']['id3v2'] ?? $getid['tags']['id3v1'] ?? [];

        $tags = [];
        foreach (self::AUDIO_TAG_KEYS as $key) {
            $tags[$key] = htmlspecialchars(iconv('windows-1251', 'UTF-8', $tagsArray[$key][0] ?? ''));
        }

        return $tags;
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
