<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Controllers\Admin;

use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\News\Application\Exceptions\ArticleNotFoundException;
use Johncms\Modules\News\Application\Services\NewsListControlsMapper;
use Johncms\Modules\News\Application\UseCases\DeleteArticleUseCase;
use Johncms\Modules\News\Application\UseCases\GetArticleUseCase;
use Johncms\NavChain;

/**
 * Deleting an article. The confirmation is a dialog: the modal of the panel asks for it with
 * X-Requested-With and gets the dialog alone, a plain visit gets it as a page.
 *
 * The list sends where it was in "back", so the form returns to the same page of the same filter.
 */
final readonly class DeleteArticleController
{
    public function __construct(
        private NavChain $navChain,
        private GetArticleUseCase $getArticle,
        private DeleteArticleUseCase $deleteArticle,
        private NewsListControlsMapper $controls,
        private Session $session,
    ) {
    }

    public function index(Request $request, int $id): ViewResponse
    {
        try {
            $article = $this->getArticle->execute($id);
        } catch (ArticleNotFoundException) {
            pageNotFound('@admin/pages/errors/404.twig');
        }

        $data = [
            'name'        => $article->name,
            'form_action' => '/admin/news/articles/' . $article->id . '/delete',
            'back_url'    => $this->controls->returnUrl($request->queryParam('back')),
        ];

        if ($request->isXmlHttpRequest()) {
            return new ViewResponse('@news/admin/delete-article-dialog.twig', $data);
        }

        $title = __('Delete article');
        $this->navChain->add(__('News'), NewsListControlsMapper::PATH);
        $this->navChain->add($title);

        return new ViewResponse('@news/admin/delete-article.twig', [
            // The dialog carries the heading itself.
            'title'       => $title,
            'module_menu' => ['news' => true],
            'dialog'      => $data,
        ]);
    }

    public function delete(Request $request, int $id): ViewResponse
    {
        try {
            $this->deleteArticle->execute($id);
        } catch (ArticleNotFoundException) {
            pageNotFound('@admin/pages/errors/404.twig');
        }

        $this->session->flash('success_message', __('The article was successfully deleted'));

        redirect($this->controls->returnUrl($request->body('back')));
    }
}
