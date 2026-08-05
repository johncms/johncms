<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\News\Application\Article;
use Johncms\Modules\News\Application\MetaTagsManager;
use Johncms\Modules\News\Application\Section;
use Johncms\NavChain;
use Johncms\Users\User;

final readonly class ArticleController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private NavChain $navChain,
        private MetaTagsManager $metaTagsManager,
        private User $currentUser,
    ) {
        $this->controllerContext->initModule('news');
        $this->navChain->add(__('News'), '/news/');
    }

    /**
     * Article page
     *
     * @param Section $section
     * @param Article $article
     * @param string $article_code
     * @param string $category
     */
    public function index(Section $section, Article $article, string $article_code, string $category = ''): ViewResponse
    {
        $section->checkPath($category);
        $current_article = $article->getArticle($article_code);

        return new ViewResponse(
            '@news/public/article.twig',
            $this->metaTagsManager->setForArticle($current_article)->toArray() + [
                'article'         => $current_article,
                'current_section' => $section->getLastSection(),
                'can_write'       => $this->currentUser->isValid() && empty($this->currentUser->ban),
            ]
        );
    }
}
