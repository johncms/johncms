<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\PageMeta;
use Johncms\Modules\Library\Domain\Models\LibraryText;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\Legacy\Tools;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Johncms\Modules\Library\Application\Services\Hashtags;

final readonly class TagsController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private Request $request,
        private Tools $tools,
        private User $currentUser,
    ) {
        $this->controllerContext->initModule('library');
    }

    public function __invoke(): string
    {
        $this->navChain->add(__('Library'), '/library/');
        $this->navChain->add(__('Tags'));

        $tag = trim((string) $this->request->getQuery('tag', ''));

        if ($tag === '') {
            http_response_code(404);
            return $this->render->render('system::pages/result', [
                'title'    => __('Tags'),
                'type'     => 'alert-info',
                'message'  => __('The list is empty'),
                'back_url' => '/library/',
            ]);
        }

        $articleIds = (new Hashtags(0))->getAllTagStats($tag);

        if (! $articleIds) {
            return $this->render->render('system::pages/result', [
                'title'    => __('Tags'),
                'type'     => 'alert-info',
                'message'  => __('The list is empty'),
                'back_url' => '/library/',
            ]);
        }

        $total = count($articleIds);
        $kmess = $this->currentUser->config->kmess;
        $page  = max(1, (int) $this->request->getQuery('page', 1));
        $page  = min($page, (int) ceil($total / $kmess));

        $pageTitle     = __('Articles tagged: %s', htmlspecialchars($tag));
        $documentTitle = $pageTitle . ' — ' . __('Library');
        $meta          = new PageMeta($documentTitle, $page);
        $this->render->addData([
            'title'       => $meta->title,
            'page_title'  => $pageTitle,
            'description' => $meta->description,
        ]);

        $ids = array_slice($articleIds, ($page - 1) * $kmess, $kmess);

        $articles = LibraryText::query()
            ->selectRaw('`id`, `name`, `time`, `uploader`, `uploader_id`, `count_views`, `comm_count`, `comments`, SUBSTRING(`text`, 1, 200) as `text_preview`')
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $list = [];
        foreach ($ids as $articleId) {
            $article = $articles->get($articleId);
            if ($article === null) {
                continue;
            }
            $uploader = $article->uploader_id
                ? '<a href="' . config('johncms')['homeurl'] . '/profile/?user=' . $article->uploader_id . '">' . $this->tools->checkout($article->uploader) . '</a>'
                : $this->tools->checkout($article->uploader);

            $tags      = (new Hashtags($article->id))->getAllStatTags(1);
            $list[] = [
                'id'          => $article->id,
                'name'        => $article->name,
                'text'        => $this->tools->checkout(strip_tags((string) $article->text_preview)),
                'cover'       => file_exists(UPLOAD_PATH . 'library/images/small/' . $article->id . '.png'),
                'who'         => $uploader . ' (' . $this->tools->displayDate($article->time) . ')',
                'count_views' => $article->count_views,
                'comm_count'  => $article->comm_count,
                'comments'    => $article->comments,
                'tags'        => $tags,
            ];
        }

        return $this->render->render('library::tags', [
            'total'      => $total,
            'list'       => $list,
            'tag'        => $tag,
            'pagination' => $this->tools->displayPagination('/library/tags?tag=' . urlencode($tag) . '&', ($page - 1) * $kmess, $total, $kmess),
        ]);
    }
}
