<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Controllers;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Library\Application\Services\LibraryArticlePathService;
use Johncms\Modules\Library\Application\Services\LibraryCategoryPathService;
use Johncms\Modules\Library\Application\Services\LibrarySlugService;
use Johncms\Modules\Library\Domain\Models\LibraryCategory;
use Johncms\Modules\Library\Domain\Models\LibraryText;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\Legacy\Tools;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Johncms\Modules\Library\Application\Services\Hashtags;
use Johncms\Modules\Library\Application\Services\Utils;

final readonly class CreateArticleController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private Request $request,
        private Tools $tools,
        private User $currentUser,
        private LibrarySlugService $slugService,
        private LibraryArticlePathService $articlePathService,
        private LibraryCategoryPathService $categoryPathService,
    ) {
        $this->controllerContext->initModule('library');
    }

    public function __invoke(): string
    {
        $catId = max(0, (int) $this->request->getQuery('id', 0));
        $isAdmin = $this->currentUser->rights > 4;

        $this->navChain->add(__('Library'), '/library/');
        $this->navChain->add(__('Write Article'));

        $this->render->addData([
            'title'      => __('Write Article'),
            'page_title' => __('Write Article'),
        ]);

        $category = LibraryCategory::query()->find($catId);

        if (! $isAdmin && (! $this->currentUser->isValid() || $category === null)) {
            http_response_code(404);
            return $this->render->render('system::pages/result', [
                'title'         => __('Write Article'),
                'type'          => 'alert-danger',
                'message'       => __('Access denied'),
                'back_url'      => '/library/',
                'back_url_name' => __('Library'),
            ]);
        }

        $formUrl = '/library/article/create?id=' . $catId;

        if ($this->request->getMethod() === 'POST') {
            return $this->handlePost($catId, $isAdmin, $formUrl);
        }

        return $this->renderForm($catId, $formUrl, '', '', '', '', [], false, null, null);
    }

    private function handlePost(int $catId, bool $isAdmin, string $formUrl): string
    {
        $post = $this->request->getParsedBody();
        $name = mb_substr(trim((string) ($post['name'] ?? '')), 0, 100);
        $announce = mb_substr(trim((string) ($post['announce'] ?? '')), 0, 500);
        $tag = trim((string) ($post['tags'] ?? ''));
        $allowComments = isset($post['comments']) ? 1 : 0;

        $errors = [];

        $flood = $this->tools->antiflood();
        if ($flood) {
            $errors[] = sprintf(__('You cannot add the Article so often<br>Please, wait %d sec.'), $flood);
            return $this->renderForm($catId, $formUrl, $name, $announce, (string) ($post['text'] ?? ''), $tag, $errors, false, null);
        }

        if (empty($name)) {
            $errors[] = __('You have not entered the name');
        }

        $text = '';
        $files = $this->request->getUploadedFiles();
        $textFile = $files['textfile'] ?? null;

        if ($textFile !== null && $textFile->getClientFilename()) {
            $ext = pathinfo($textFile->getClientFilename(), PATHINFO_EXTENSION);
            if (mb_strtolower($ext) === 'txt') {
                $content = (string) $textFile->getStream();
                if (mb_check_encoding($content, 'windows-1251')) {
                    $content = (string) iconv('windows-1251', 'UTF-8', $content);
                } elseif (mb_check_encoding($content, 'KOI8-R')) {
                    $content = (string) iconv('KOI8-R', 'UTF-8', $content);
                } else {
                    $errors[] = __('The file is invalid encoding, preferably UTF-8');
                }
                $text = $this->plainTextToHtml(trim($content));
            } else {
                $errors[] = __('Invalid file format allowed * .txt');
            }
        } else {
            $text = trim((string) ($post['text'] ?? ''));
            if (empty($text)) {
                $errors[] = __('You have not entered text');
            }
        }

        if (! empty($errors)) {
            return $this->renderForm($catId, $formUrl, $name, $announce, (string) ($post['text'] ?? ''), $tag, $errors, false, null);
        }

        $slug = $this->slugService->generateArticleSlug($name, $catId);

        $article = LibraryText::query()->create([
            'cat_id'      => $catId,
            'name'        => $name,
            'slug'        => $slug,
            'announce'    => $announce,
            'text'        => $text,
            'uploader'    => $this->currentUser->name,
            'uploader_id' => $this->currentUser->id,
            'premod'      => $isAdmin ? 1 : 0,
            'comments'    => $allowComments,
            'time'        => time(),
        ]);

        $cid = $article->id;

        $imageFile = $files['image'] ?? null;
        if ($imageFile !== null && $imageFile->getClientFilename()) {
            try {
                Utils::imageUpload($cid, $imageFile);
            } catch (\Exception) {
                $errors[] = __('Photo uploading error');
            }
        }

        if (! empty($tag)) {
            $tags = array_map('trim', explode(',', $tag));
            if (count($tags)) {
                $obj = new Hashtags($cid);
                $obj->addTags($tags);
                $obj->delCache();
            }
        }

        Capsule::table('users')->where('id', $this->currentUser->id)->update(['lastpost' => time()]);

        $articleUrl = $cid !== null ? $this->articlePathService->getArticleUrlById($cid) : null;

        return $this->renderForm($catId, $formUrl, '', '', '', '', $errors, $isAdmin, $cid, $articleUrl);
    }

    private function renderForm(
        int $catId,
        string $formUrl,
        string $name,
        string $announce,
        string $text,
        string $tag,
        array $errors,
        bool $approved,
        ?int $cid,
        ?string $articleUrl,
    ): string {
        $catUrl = $this->categoryPathService->getCategoryUrlById($catId) ?? '/library/';

        return $this->render->render('library::article_create', [
            'form_url'    => $formUrl,
            'cat_id'      => $catId,
            'cat_url'     => $catUrl,
            'name'        => $name,
            'announce'    => $announce,
            'text'        => $text,
            'tag'         => $tag,
            'errors'      => $errors,
            'success'     => $cid !== null && empty($errors),
            'approved'    => $approved,
            'cid'         => $cid,
            'article_url' => $articleUrl,
        ]);
    }

    /**
     * Converts a plain text file upload into simple paragraph HTML.
     */
    private function plainTextToHtml(string $text): string
    {
        $lines = preg_split('/\R/u', $text) ?: [];
        $paragraphs = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line !== '') {
                $paragraphs[] = '<p>' . htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . '</p>';
            }
        }

        return implode('', $paragraphs);
    }
}
