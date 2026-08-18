<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Controllers;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\CurrentUser;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Library\Application\Services\Hashtags;
use Johncms\Modules\Library\Application\Services\LibraryPermissions;
use Johncms\Modules\Library\Application\Services\LibrarySlugService;
use Johncms\Modules\Library\Application\Services\Tree;
use Johncms\Modules\Library\Application\Services\Utils;
use Johncms\Modules\Library\Domain\Models\LibraryCategory;
use Johncms\Modules\Library\Domain\Models\LibraryText;
use Johncms\NavChain;
use Symfony\Component\HttpFoundation\Response;
use Johncms\Modules\Library\Infrastructure\Storage\LibraryCoverSize;
use Johncms\Modules\Library\Infrastructure\Storage\LibraryCoverStorage;

final readonly class EditArticleController
{
    /** Longer texts are not offered to the editor: the browser does not cope with them. */
    private const EDITABLE_TEXT_LENGTH = 500000;

    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private NavChain $navChain,
        private CurrentUser $currentUser,
        private LibrarySlugService $slugService,
        private LibraryCoverStorage $covers,
    ) {
    }

    public function __invoke(Request $request, int $id): ViewResponse
    {
        $article = LibraryText::query()->find($id);

        if ($article === null) {
            return new ViewResponse(
                '@theme/pages/result.twig',
                [
                    'title'   => __('Edit Article'),
                    'type'    => 'alert-danger',
                    'message' => __('Articles do not exist'),
                ],
                Response::HTTP_NOT_FOUND
            );
        }

        $isAdmin = $this->accessChecker->allows(LibraryPermissions::MODERATE);
        $isOwner = $this->currentUser->isValid()
            && (int) $article->uploader_id === $this->currentUser->id();

        if (! $isAdmin && ! $isOwner) {
            return new ViewResponse(
                '@theme/pages/result.twig',
                [
                    'title'   => __('Edit Article'),
                    'type'    => 'alert-danger',
                    'message' => __('Access forbidden'),
                ],
                Response::HTTP_FORBIDDEN
            );
        }

        $this->navChain->add(__('Library'), '/library/');
        $dirNav = new Tree($article->cat_id);
        $dirNav->processNavPanel();
        $dirNav->printNavPanel();
        $this->navChain->add($article->name, $article->url);
        $this->navChain->add(__('Edit Article'));

        $pageData = [
            'title'      => __('Edit Article'),
            'page_title' => __('Edit Article'),
            'id'         => $id,
        ];

        if ($request->getMethod() === 'POST') {
            $this->save($request, $id, $article, $isAdmin);
            $article->refresh();

            return new ViewResponse('@library/public/edit-article.twig', $pageData + [
                'article_url' => $article->url,
                'saved'       => true,
            ]);
        }

        $categories = [];
        if ($isAdmin) {
            foreach (LibraryCategory::query()->where('dir', 0)->orderBy('name')->get(['id', 'name']) as $category) {
                $categories[] = ['id' => (int) $category->id, 'name' => $category->name];
            }
        }

        return new ViewResponse('@library/public/edit-article.twig', $pageData + [
            'article_url'   => $article->url,
            'saved'         => false,
            'name'          => $article->name,
            'announce'      => (string) ($article->announce ?? ''),
            'text'          => (string) ($article->text ?? ''),
            'text_editable' => mb_strlen((string) $article->text) < self::EDITABLE_TEXT_LENGTH,
            'tags'          => implode(', ', (new Hashtags($id))->getTagNames()),
            'cover'         => $this->covers->exists($id, LibraryCoverSize::Small),
            'is_admin'      => $isAdmin,
            'categories'    => $categories,
            'cat_id'        => (int) $article->cat_id,
            'premod'        => $article->premod > 0,
            'comments'      => $article->comments > 0,
            'count_views'   => (int) $article->count_views,
            'field_height'  => $this->currentUser->user()->config->fieldHeight,
        ]);
    }

    private function save(Request $request, int $id, LibraryText $article, bool $isAdmin): void
    {
        $post = $request->request->all();

        if (isset($post['tags'])) {
            $obj = new Hashtags($id);
            $obj->delTags();
            $obj->delCache();
            $tags = array_filter(array_map('trim', explode(',', (string) ($post['tags'] ?? ''))));
            if ($tags) {
                $obj->addTags($tags);
            }
        }

        $files = $request->files->all();
        $screen = $files['image'] ?? null;
        if ($screen !== null && $screen->getClientOriginalName()) {
            try {
                Utils::imageUpload($id, $screen);
            } catch (\Exception) {
            }
        }

        $newName  = mb_substr(trim((string) ($post['name'] ?? '')), 0, 100);
        $newCatId = isset($post['move']) ? (int) $post['move'] : (int) $article->cat_id;

        $nameChanged = $newName !== $article->name;
        $catChanged  = $newCatId !== (int) $article->cat_id;

        $fields = ['name' => $newName];

        if ($nameChanged || $catChanged) {
            $fields['slug'] = $this->slugService->generateArticleSlug($newName, $newCatId, $id);
        }

        if (($post['text'] ?? '') !== 'do_not_change') {
            $fields['text'] = trim((string) ($post['text'] ?? ''));
        }

        if (! empty($post['announce'])) {
            $fields['announce'] = mb_substr(trim((string) $post['announce']), 0, 500);
        }

        if ($catChanged) {
            $fields['cat_id'] = $newCatId;
        }

        if ($isAdmin) {
            $fields['count_views'] = max(0, (int) ($post['count_views'] ?? 0));
            $fields['premod']      = isset($post['premod']) ? 1 : 0;
            $fields['comments']    = isset($post['comments']) ? 1 : 0;
        }

        $article->update($fields);
    }
}
