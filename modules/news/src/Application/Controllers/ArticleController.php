<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\News\Application\Article;
use Johncms\Modules\News\Application\MetaTagsManager;
use Johncms\Modules\News\Application\Section;
use Johncms\NavChain;
use Johncms\System\View\Render;

final readonly class ArticleController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private MetaTagsManager $metaTagsManager,
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
     * @return string
     */
    public function index(Section $section, Article $article, string $article_code, string $category = ''): string
    {
        $section->checkPath($category);
        $current_article = $article->getArticle($article_code);
        $this->render->addData($this->metaTagsManager->setForArticle($current_article)->toArray());
        return $this->render->render(
            'news::public/article',
            [
                'article' => $current_article,
                'current_section' => $section->getLastSection(),
            ]
        );
    }
}
