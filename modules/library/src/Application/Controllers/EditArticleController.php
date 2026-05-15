<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Library\Domain\Models\LibraryCategory;
use Johncms\Modules\Library\Domain\Models\LibraryText;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\Legacy\Bbcode;
use Johncms\System\Legacy\Tools;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Johncms\Modules\Library\Application\Services\Hashtags;
use Johncms\Modules\Library\Application\Services\Tree;
use Johncms\Modules\Library\Application\Services\Utils;

final readonly class EditArticleController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private Request $request,
        private Tools $tools,
        private Bbcode $bbcode,
        private User $currentUser,
    ) {
        $this->controllerContext->initModule('library');
    }

    public function __invoke(int $id): string
    {
        $article = LibraryText::query()->find($id);

        if ($article === null) {
            http_response_code(404);
            return $this->render->render('system::pages/result', [
                'title'   => __('Edit Article'),
                'type'    => 'alert-danger',
                'message' => __('Articles do not exist'),
            ]);
        }

        $isAdmin = $this->currentUser->rights > 4;
        $isOwner = $this->currentUser->isValid()
            && (int) $article->uploader_id === (int) $this->currentUser->id;

        if (! $isAdmin && ! $isOwner) {
            http_response_code(403);
            return $this->render->render('system::pages/result', [
                'title'   => __('Edit Article'),
                'type'    => 'alert-danger',
                'message' => __('Access forbidden'),
            ]);
        }

        $this->navChain->add(__('Library'), '/library/');
        $dirNav = new Tree($article->cat_id);
        $dirNav->processNavPanel();
        $dirNav->printNavPanel();
        $this->navChain->add($this->tools->checkout($article->name), '/library/?id=' . $id);
        $this->navChain->add(__('Edit Article'));

        $this->render->addData([
            'title'      => __('Edit Article'),
            'page_title' => __('Edit Article'),
        ]);

        if ($this->request->getMethod() === 'POST') {
            $this->save($id, $article, $isAdmin);
            return $this->render->render('library::edit_article', [
                'id'    => $id,
                'saved' => true,
            ]);
        }

        $categories = $isAdmin
            ? LibraryCategory::query()->where('dir', 0)->orderBy('name')->get(['id', 'name'])
            : collect();

        $tags = (new Hashtags($id))->getAllStatTags() ?: '';

        return $this->render->render('library::edit_article', [
            'id'         => $id,
            'article'    => $article,
            'categories' => $categories,
            'tags'       => $tags,
            'isAdmin'    => $isAdmin,
            'saved'      => false,
            'bbcode'     => $this->bbcode->buttons('form', 'text'),
        ]);
    }

    private function save(int $id, LibraryText $article, bool $isAdmin): void
    {
        $post = $this->request->getParsedBody();

        if (isset($post['tags'])) {
            $obj = new Hashtags($id);
            $obj->delTags();
            $obj->delCache();
            $tags = array_filter(array_map('trim', explode(',', (string) ($post['tags'] ?? ''))));
            if ($tags) {
                $obj->addTags($tags);
            }
        }

        $files = $this->request->getUploadedFiles();
        $screen = $files['image'] ?? null;
        if ($screen !== null && $screen->getClientFilename()) {
            try {
                Utils::imageUpload($id, $screen);
            } catch (\Exception) {
            }
        }

        $fields = [
            'name' => mb_substr(trim((string) ($post['name'] ?? '')), 0, 100),
        ];

        if (($post['text'] ?? '') !== 'do_not_change') {
            $fields['text'] = trim((string) ($post['text'] ?? ''));
        }

        if (! empty($post['announce'])) {
            $fields['announce'] = mb_substr(trim((string) $post['announce']), 0, 500);
        }

        if (isset($post['move'])) {
            $fields['cat_id'] = (int) $post['move'];
        }

        if ($isAdmin) {
            $fields['count_views'] = max(0, (int) ($post['count_views'] ?? 0));
            $fields['premod']      = isset($post['premod']) ? 1 : 0;
            $fields['comments']    = isset($post['comments']) ? 1 : 0;
        }

        $article->update($fields);
    }
}
