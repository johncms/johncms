<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\News\Application\Article;
use Johncms\Modules\News\Application\MetaTagsManager;
use Johncms\Modules\News\Application\Section;
use Johncms\NavChain;
use Johncms\System\View\Render;

final readonly class SectionController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private NavChain $navChain,
        private MetaTagsManager $metaTagsManager,
        private Render $render,
    ) {
        $this->controllerContext->initModule('news');
        $this->navChain->add(__('News'), '/news/');
    }

    /**
     * List of articles and sections
     *
     * @param Article $article
     * @param Section $section
     * @param string $category
     * @return string
     */
    public function index(Article $article, Section $section, string $category = ''): string
    {
        $section->checkPath($category);
        $current_section = $section->getLastSection();
        $this->render->addData($this->metaTagsManager->setForSection($current_section)->toArray());
        return $this->render->render(
            'news::public/index',
            [
                'sections' => $section->getSections($current_section->id ?? 0),
                'articles' => $article->getArticles($section->getCachedSubsections($current_section)),
                'current_section' => $current_section,
            ]
        );
    }
}
