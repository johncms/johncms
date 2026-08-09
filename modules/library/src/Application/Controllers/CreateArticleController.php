<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Controllers;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Modules\Library\Application\Services\LibraryArticlePathService;
use Johncms\Modules\Library\Application\Services\LibraryCategoryPathService;
use Johncms\Modules\Library\Application\Services\LibrarySlugService;
use Johncms\Modules\Library\Domain\Models\LibraryCategory;
use Johncms\Modules\Library\Domain\Models\LibraryText;
use Johncms\NavChain;
use Johncms\Http\View\ViewResponse;
use Johncms\Security\AntifloodCheckerInterface;
use Johncms\Http\Request;
use Johncms\Users\User;
use Johncms\Modules\Library\Application\Services\Hashtags;
use Johncms\Modules\Library\Application\Services\Utils;
use Symfony\Component\HttpFoundation\Response;
use Twig\Markup;

final readonly class CreateArticleController
{
    public function __construct(
        private NavChain $navChain,
        private AntifloodCheckerInterface $antifloodChecker,
        private User $currentUser,
        private LibrarySlugService $slugService,
        private LibraryArticlePathService $articlePathService,
        private LibraryCategoryPathService $categoryPathService,
    ) {
    }

    public function __invoke(Request $request): ViewResponse
    {
        $catId = max(0, $request->queryInt('id', 0));
        $isAdmin = $this->currentUser->rights > 4;

        $this->navChain->add(__('Library'), '/library/');
        $this->navChain->add(__('Write Article'));

        $category = LibraryCategory::query()->find($catId);

        if (! $isAdmin && (! $this->currentUser->isValid() || $category === null)) {
            return new ViewResponse(
                '@theme/pages/result.twig',
                [
                    'title'         => __('Write Article'),
                    'type'          => 'alert-danger',
                    'message'       => __('Access denied'),
                    'back_url'      => '/library/',
                    'back_url_name' => __('Library'),
                ],
                Response::HTTP_NOT_FOUND
            );
        }

        $formUrl = '/library/article/create?id=' . $catId;

        if ($request->getMethod() === 'POST') {
            return $this->handlePost($request, $catId, $isAdmin, $formUrl);
        }

        return $this->renderForm($catId, $formUrl, '', '', '', '', [], false, null, null);
    }

    private function handlePost(Request $request, int $catId, bool $isAdmin, string $formUrl): ViewResponse
    {
        $post = $request->request->all();
        $name = mb_substr(trim((string) ($post['name'] ?? '')), 0, 100);
        $announce = mb_substr(trim((string) ($post['announce'] ?? '')), 0, 500);
        $tag = trim((string) ($post['tags'] ?? ''));
        $allowComments = isset($post['comments']) ? 1 : 0;

        $errors = [];

        $flood = $this->antifloodChecker->getRemainingSeconds();
        if ($flood) {
            // The message breaks over two lines, so the markup is part of the translated string.
            $errors[] = new Markup(__('You cannot add the Article so often<br>Please, wait %d sec.', $flood), 'UTF-8');
            return $this->renderForm($catId, $formUrl, $name, $announce, (string) ($post['text'] ?? ''), $tag, $errors, false, null, null);
        }

        if (empty($name)) {
            $errors[] = __('You have not entered the name');
        }

        $text = '';
        $files = $request->files->all();
        $textFile = $files['textfile'] ?? null;

        if ($textFile !== null && $textFile->getClientOriginalName()) {
            $ext = pathinfo($textFile->getClientOriginalName(), PATHINFO_EXTENSION);
            if (mb_strtolower($ext) === 'txt') {
                $content = (string) file_get_contents($textFile->getPathname());
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
            return $this->renderForm($catId, $formUrl, $name, $announce, (string) ($post['text'] ?? ''), $tag, $errors, false, null, null);
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
        if ($imageFile !== null && $imageFile->getClientOriginalName()) {
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
    ): ViewResponse {
        $catUrl = $this->categoryPathService->getCategoryUrlById($catId) ?? '/library/';

        return new ViewResponse('@library/public/create-article.twig', [
            'title'        => __('Write Article'),
            'page_title'   => __('Write Article'),
            'form_url'     => $formUrl,
            'cat_url'      => $catUrl,
            'name'         => $name,
            'announce'     => $announce,
            'text'         => $text,
            'tags'         => $tag,
            'errors'       => $errors,
            'success'      => $cid !== null && empty($errors),
            'approved'     => $approved,
            'article_url'  => $articleUrl,
            'field_height' => $this->currentUser->config->fieldHeight,
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
