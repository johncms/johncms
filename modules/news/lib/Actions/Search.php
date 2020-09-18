<?php

declare(strict_types=1);

namespace News\Actions;

use News\Models\NewsArticle;
use News\Utils\AbstractAction;

class Search extends AbstractAction
{
    /**
     * The search by tags page
     */
    public function byTags(): void
    {
        $this->nav_chain->add(__('News'), '/news/');
        $page_title = __('Search by tags');
        $this->nav_chain->add($page_title, '');

        $query = $this->request->getQuery('tag', '', FILTER_SANITIZE_STRING);

        if (! empty($query)) {
            $articles = (new NewsArticle())->where('tags', 'like', '%' . $query . '%')->paginate($this->user->config->kmess);
        }

        $this->render->addData(
            [
                'title'       => $page_title,
                'page_title'  => $page_title,
                'keywords'    => $this->settings['meta_keywords'],
                'description' => $this->settings['meta_description'],
            ]
        );

        echo $this->render->render(
            'news::public/search_by_tags',
            [
                'query'    => $query,
                'articles' => $articles ?? null,
            ]
        );
    }

    /**
     * The search by tags page
     */
    public function index(): void
    {
        $this->nav_chain->add(__('News'), '/news/');
        $page_title = __('Search');
        $this->nav_chain->add($page_title, '');

        $query = $this->request->getQuery('query', '', FILTER_SANITIZE_STRING);

        if (! empty($query)) {
            $articles = (new NewsArticle())->search()->where('news_search_index.text', 'like', '%' . $query . '%')->paginate($this->user->config->kmess);
        }

        $this->render->addData(
            [
                'title'       => $page_title,
                'page_title'  => $page_title,
                'keywords'    => $this->settings['meta_keywords'],
                'description' => $this->settings['meta_description'],
            ]
        );

        echo $this->render->render(
            'news::public/search',
            [
                'query'    => $query,
                'articles' => $articles ?? null,
            ]
        );
    }
}
