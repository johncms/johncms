<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Library\Domain\Models\LibraryText;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\System\View\Render;
use Johncms\Modules\Library\Application\Services\Hashtags;
use Johncms\Utils\DateFormatterInterface;
use Johncms\Utils\PlainTextFormatter;
use Symfony\Component\HttpFoundation\Response;

final readonly class TagsController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private DateFormatterInterface $dateFormatter,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
        $this->controllerContext->initModule('library');
    }

    public function __invoke(Request $request): Response
    {
        $this->navChain->add(__('Library'), '/library/');
        $this->navChain->add(__('Tags'));

        $tag = trim($request->queryParam('tag', ''));

        if ($tag === '') {
            return new Response(
                $this->render->render('system::pages/result', [
                    'title'    => __('Tags'),
                    'type'     => 'alert-info',
                    'message'  => __('The list is empty'),
                    'back_url' => '/library/',
                ]),
                Response::HTTP_NOT_FOUND
            );
        }

        $articleIds = (new Hashtags(0))->getAllTagStats($tag);

        if (! $articleIds) {
            return new Response($this->render->render('system::pages/result', [
                'title'    => __('Tags'),
                'type'     => 'alert-info',
                'message'  => __('The list is empty'),
                'back_url' => '/library/',
            ]));
        }

        $total = count($articleIds);

        $pagination = $this->paginationFactory->create($total);
        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $pageTitle     = __('Articles tagged: %s', htmlspecialchars($tag));
        $documentTitle = $pageTitle . ' — ' . __('Library');
        $meta          = new PageMeta($documentTitle, $pagination->getCurrentPage());
        $this->render->addData([
            'title'       => $meta->title,
            'page_title'  => $pageTitle,
            'description' => $meta->description,
        ]);

        $ids = array_slice($articleIds, $pagination->getOffset(), $pagination->getPerPage());

        $articles = LibraryText::query()
            ->selectRaw('`id`, `cat_id`, `slug`, `name`, `time`, `uploader`, `uploader_id`, `count_views`, `comm_count`, `comments`, SUBSTRING(`text`, 1, 200) as `text_preview`')
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
                ? '<a href="' . config('johncms')['homeurl'] . '/profile/' . $article->uploader_id . '">' . PlainTextFormatter::escape($article->uploader) . '</a>'
                : PlainTextFormatter::escape($article->uploader);

            $tags      = (new Hashtags($article->id))->getAllStatTags(1);
            $list[] = [
                'id'          => $article->id,
                'url'         => $article->url,
                'name'        => $article->name,
                'text'        => strip_tags((string) $article->text_preview),
                'cover'       => file_exists(UPLOAD_PATH . 'library/images/small/' . $article->id . '.png'),
                'who'         => $uploader . ' (' . $this->dateFormatter->format($article->time) . ')',
                'count_views' => $article->count_views,
                'comm_count'  => $article->comm_count,
                'comments'    => $article->comments,
                'tags'        => $tags,
            ];
        }

        return new Response($this->render->render('library::tags', [
            'total'      => $total,
            'list'       => $list,
            'tag'        => $tag,
            'pagination' => $pagination->render(),
        ]));
    }
}
