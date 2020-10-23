<?php

declare(strict_types=1);

namespace News\Actions;

use News\Models\NewsArticle;
use News\Models\NewsSearchIndex;
use News\Models\NewsSection;
use News\Utils\AbstractAction;
use News\Utils\Helpers;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

class Article extends AbstractAction
{
    /**
     * Article page
     */
    public function index(): void
    {
        $this->nav_chain->add(__('News'), '/news/');

        $route = $this->route;
        $current_section = null;
        if (! empty($route['category'])) {
            $path = Helpers::checkPath($route['category']);
            if (! empty($path)) {
                foreach ($path as $item) {
                    /** @var $item NewsSection */
                    $this->nav_chain->add($item->name, $item->url);
                }
                /** @var NewsSection $current_section */
                $current_section = $path[array_key_last($path)];
            }
        }

        try {
            $article = (new NewsArticle())->where('code', $route['article'])->firstOrFail();
        } catch (ModelNotFoundException $exception) {
            pageNotFound();
        }

        // Фиксируем количество просмотров
        if (empty($_SESSION['news_viewed_articles']) || ! in_array($article->id, $_SESSION['news_viewed_articles'], true)) {
            ++$article->view_count;
            $article->save();
            $_SESSION['news_viewed_articles'][] = $article->id;
        }

        $page_title = $article->name;
        $this->nav_chain->add($page_title, $article->url);

        $this->render->addData(
            [
                'title'       => $article->meta_title,
                'page_title'  => $page_title,
                'keywords'    => $article->meta_keywords,
                'description' => $article->meta_description,
            ]
        );

        echo $this->render->render(
            'news::public/article',
            [
                'article'         => $article,
                'current_section' => $current_section,
            ]
        );
    }

    /**
     * Article creation page
     */
    public function add(): void
    {
        $this->nav_chain->add(__('Section list'), '/news/admin/content/');
        $this->render->addData(
            [
                'title'      => __('Add article'),
                'page_title' => __('Add article'),
            ]
        );

        $section_id = $this->request->getQuery('section_id', 0, FILTER_VALIDATE_INT);

        if (! empty($section_id)) {
            try {
                $current_section = (new NewsSection())->findOrFail($section_id);

                Helpers::buildAdminBreadcrumbs($current_section->parentSection);

                // Adding the current section to the navigation chain
                $this->nav_chain->add($current_section->name, '/news/admin/content/?section_id=' . $current_section->id);
            } catch (ModelNotFoundException $exception) {
                pageNotFound();
            }
        }

        $this->nav_chain->add(__('Add article'));

        $data = [
            'action_url' => '/news/admin/add_article/?section_id=' . $section_id,
            'back_url'   => '/news/admin/content/?section_id=' . $section_id,
            'section_id' => $section_id,
            'fields'     => [
                'active'       => (int) $this->request->getPost('active', 1),
                'section_id'   => $section_id,
                'name'         => $this->request->getPost('name', '', FILTER_SANITIZE_STRING),
                'page_title'   => $this->request->getPost('page_title', '', FILTER_SANITIZE_STRING),
                'code'         => $this->request->getPost('code', '', FILTER_SANITIZE_STRING),
                'keywords'     => $this->request->getPost('keywords', '', FILTER_SANITIZE_STRING),
                'description'  => $this->request->getPost('description', '', FILTER_SANITIZE_STRING),
                'tags'         => $this->request->getPost('tags', '', FILTER_SANITIZE_STRING),
                'preview_text' => $this->request->getPost('preview_text', ''),
                'text'         => $this->request->getPost('text', ''),
            ],
        ];

        $data['fields'] = array_map('trim', $data['fields']);

        $errors = [];
        // Processing the sent data from the form.
        if ($this->request->getMethod() === 'POST') {
            if (empty($data['fields']['name'])) {
                $errors[] = __('The article name cannot be empty');
            }

            // Code generation
            if (empty($data['fields']['code'])) {
                $data['fields']['code'] = Str::slug($data['fields']['name']);
            } else {
                $data['fields']['code'] = Str::slug($data['fields']['code']);
            }

            if (empty($errors)) {
                $check = false;
                if (! empty($section_id)) {
                    $check = (new NewsArticle())
                        ->where('code', $data['fields']['code'])
                        ->where('section_id', $section_id)
                        ->first();
                }

                if (! $check) {
                    $data['fields']['created_by'] = $this->user->id;
                    $created_article = (new NewsArticle())->create($data['fields']);

                    $search_text = strip_tags($created_article->name . ' ' . $created_article->preview_text . ' ' . $created_article->text);
                    (new NewsSearchIndex())->create(
                        [
                            'article_id' => $created_article->id,
                            'text'       => $search_text,
                        ]
                    );
                    $_SESSION['success_message'] = __('The article was created successfully');
                    header('Location: /news/admin/content/?section_id=' . $section_id);
                    exit;
                }
                $errors[] = __('An article with this code already exists');
            }
        }

        $data['errors'] = $errors;

        echo $this->render->render('news::admin/add_article', ['data' => $data]);
    }

    /**
     * The edit article page
     */
    public function edit(): void
    {
        $this->nav_chain->add(__('Section list'), '/news/admin/content/');
        $this->render->addData(
            [
                'title'      => __('Edit article'),
                'page_title' => __('Edit article'),
            ]
        );

        $article_id = $this->request->getQuery('article_id', 0, FILTER_VALIDATE_INT);
        try {
            $article = (new NewsArticle())->findOrFail($article_id);
        } catch (ModelNotFoundException $exception) {
            pageNotFound();
        }

        Helpers::buildAdminBreadcrumbs($article->parentSection);
        $this->nav_chain->add($article->name);

        $data = [
            'action_url' => '/news/admin/edit_article/?article_id=' . $article->id,
            'back_url'   => '/news/admin/content/?section_id=' . $article->section_id,
            'article_id' => $article_id,
            'fields'     => [
                'active'       => (int) $this->request->getPost('active', $article->active),
                'name'         => $this->request->getPost('name', $article->name, FILTER_SANITIZE_STRING),
                'page_title'   => $this->request->getPost('page_title', $article->page_title, FILTER_SANITIZE_STRING),
                'code'         => $this->request->getPost('code', $article->code, FILTER_SANITIZE_STRING),
                'keywords'     => $this->request->getPost('keywords', $article->keywords, FILTER_SANITIZE_STRING),
                'description'  => $this->request->getPost('description', $article->description, FILTER_SANITIZE_STRING),
                'tags'         => $this->request->getPost('tags', implode(', ', $article->tags), FILTER_SANITIZE_STRING),
                'preview_text' => $this->request->getPost('preview_text', $article->preview_text),
                'text'         => $this->request->getPost('text', $article->text),
            ],
        ];

        $data['fields'] = array_map('trim', $data['fields']);

        $errors = [];
        // Processing the sent data from the form.
        if ($this->request->getMethod() === 'POST') {
            if (empty($data['fields']['name'])) {
                $errors[] = __('The article name cannot be empty');
            }

            // Code generation
            if (empty($data['fields']['code'])) {
                $data['fields']['code'] = Str::slug($data['fields']['name']);
            } else {
                $data['fields']['code'] = Str::slug($data['fields']['code']);
            }

            if (empty($errors)) {
                $check = (new NewsArticle())
                    ->where('code', $data['fields']['code'])
                    ->where('section_id', $article->section_id)
                    ->where('id', '!=', $article->id)
                    ->first();

                if (! $check) {
                    $data['fields']['updated_by'] = $this->user->id;
                    $article->update($data['fields']);

                    $search_text = strip_tags($data['fields']['name'] . ' ' . $data['fields']['preview_text'] . ' ' . $data['fields']['text']);
                    (new NewsSearchIndex())->updateOrCreate(
                        ['article_id' => $article->id],
                        ['text' => $search_text]
                    );
                    $_SESSION['success_message'] = __('The article was updated successfully');
                    header('Location: /news/admin/content/?section_id=' . $article->section_id);
                    exit;
                }
                $errors[] = __('An article with this code already exists');
            }
        }

        $data['errors'] = $errors;

        echo $this->render->render('news::admin/add_article', ['data' => $data]);
    }

    /**
     * Delete section
     */
    public function del(): void
    {
        $data = [];
        $id = $this->request->getQuery('id', 0, FILTER_VALIDATE_INT);

        // Get the section to delete
        try {
            $article = (new NewsArticle())->findOrFail($id);
        } catch (ModelNotFoundException $exception) {
            exit($exception->getMessage());
        }

        $post = $this->request->getParsedBody();

        // Checking the data and deleting the section
        if (
            isset($post['delete_token'], $_SESSION['delete_token']) &&
            $_SESSION['delete_token'] === $post['delete_token'] &&
            $this->request->getMethod() === 'POST'
        ) {
            // Delete article
            try {
                $article->delete();
            } catch (\Exception $exception) {
                exit($exception->getMessage());
            }

            $_SESSION['success_message'] = __('The article was successfully deleted');
            header('Location: /news/admin/content/?section_id=' . $article->section_id);
            exit;
        }

        $data['article'] = $article;

        // Generate the token
        $data['delete_token'] = uniqid('', true);
        $_SESSION['delete_token'] = $data['delete_token'];

        $data['action_url'] = '/news/admin/del_article/?id=' . $id;

        echo $this->render->render('news::admin/del', ['data' => $data]);
    }
}
