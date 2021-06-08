<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace News\Controllers\Admin;

use Admin\Controllers\BaseAdminController;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Psr7\UploadedFile;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use Johncms\FileInfo;
use Johncms\Files\FileStorage;
use Johncms\System\Http\Request;
use Johncms\Users\User;
use League\Flysystem\FilesystemException;
use News\Models\NewsArticle;
use News\Models\NewsSearchIndex;
use News\Models\NewsSection;
use News\Utils\Helpers;

class AdminArticleController extends BaseAdminController
{
    protected $module_name = 'news';

    protected $config;

    public function __construct()
    {
        parent::__construct();
        $this->config = di('config')['news'] ?? [];
        $this->nav_chain->add(__('News'), '/admin/news/');
        $this->render->addData(
            [
                'title'       => __('News'),
                'page_title'  => __('News'),
                'module_menu' => ['news' => true],
            ]
        );
        $this->nav_chain->add(__('Section list'), '/admin/news/content/');
    }

    /**
     * Article creation page
     *
     * @param Request $request
     * @param User $user
     * @param int $section_id
     * @return string
     */
    public function add(Request $request, User $user, int $section_id = 0): string
    {
        $this->render->addData(
            [
                'title'      => __('Add article'),
                'page_title' => __('Add article'),
            ]
        );

        if (! empty($section_id)) {
            try {
                $current_section = (new NewsSection())->findOrFail($section_id);

                Helpers::buildAdminBreadcrumbs($current_section->parentSection);

                // Adding the current section to the navigation chain
                $this->nav_chain->add($current_section->name, '/admin/news/content/' . $current_section->id);
            } catch (ModelNotFoundException $exception) {
                pageNotFound();
            }
        }

        $this->nav_chain->add(__('Add article'));

        $data = [
            'action_url' => '/admin/news/add_article/' . $section_id,
            'back_url'   => '/admin/news/content/' . $section_id,
            'section_id' => $section_id,
            'fields'     => [
                'active'       => (int) $request->getPost('active', 1),
                'section_id'   => $section_id,
                'active_from'  => $request->getPost('active_from', '', FILTER_SANITIZE_STRING),
                'active_to'    => $request->getPost('active_to', '', FILTER_SANITIZE_STRING),
                'name'         => $request->getPost('name', ''),
                'page_title'   => $request->getPost('page_title', ''),
                'code'         => $request->getPost('code', ''),
                'keywords'     => $request->getPost('keywords', ''),
                'description'  => $request->getPost('description', ''),
                'tags'         => $request->getPost('tags', ''),
                'preview_text' => $request->getPost('preview_text', ''),
                'text'         => $request->getPost('text', ''),
            ],
        ];

        $data['fields'] = array_map('trim', $data['fields']);
        $data['fields']['attached_files'] = (array) $request->getPost('attached_files', [], FILTER_VALIDATE_INT);

        $errors = [];
        // Processing the sent data from the form.
        if ($request->getMethod() === 'POST') {
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
                    $data['fields']['created_by'] = $user->id;
                    $created_article = (new NewsArticle())->create($data['fields']);

                    $search_text = $created_article->getRawOriginal('name') . strip_tags(' ' . $created_article->getRawOriginal('preview_text') . ' ' . $created_article->getRawOriginal('text'));
                    (new NewsSearchIndex())->create(
                        [
                            'article_id' => $created_article->id,
                            'text'       => $search_text,
                        ]
                    );
                    $_SESSION['success_message'] = __('The article was created successfully');
                    header('Location: /admin/news/content/' . $section_id);
                    exit;
                }
                $errors[] = __('An article with this code already exists');
            }
        }

        $data['errors'] = $errors;

        return $this->render->render('news::admin/add_article', ['data' => $data]);
    }

    /**
     * The edit article page
     *
     * @param int $article_id
     * @param Request $request
     * @param User $user
     * @return string
     */
    public function edit(int $article_id, Request $request, User $user): string
    {
        $this->render->addData(
            [
                'title'      => __('Edit article'),
                'page_title' => __('Edit article'),
            ]
        );

        try {
            $article = (new NewsArticle())->findOrFail($article_id);
        } catch (ModelNotFoundException $exception) {
            pageNotFound();
        }

        Helpers::buildAdminBreadcrumbs($article->parentSection);
        $this->nav_chain->add($article->name);

        if (! empty($article->getRawOriginal('active_from'))) {
            $active_from = Carbon::parse($article->getRawOriginal('active_from'))->format('d.m.Y H:i');
        }
        if (! empty($article->getRawOriginal('active_to'))) {
            $active_to = Carbon::parse($article->getRawOriginal('active_to'))->format('d.m.Y H:i');
        }

        $data = [
            'action_url' => '/admin/news/edit_article/' . $article->id . '/',
            'back_url'   => '/admin/news/content/' . $article->section_id . '/',
            'article_id' => $article_id,
            'fields'     => [
                'active'       => (int) $request->getPost('active', $article->active),
                'active_from'  => $request->getPost('active_from', $active_from ?? '', FILTER_SANITIZE_STRING),
                'active_to'    => $request->getPost('active_to', $active_to ?? '', FILTER_SANITIZE_STRING),
                'name'         => $request->getPost('name', $article->name),
                'page_title'   => $request->getPost('page_title', $article->page_title),
                'code'         => $request->getPost('code', $article->code),
                'keywords'     => $request->getPost('keywords', $article->keywords),
                'description'  => $request->getPost('description', $article->description),
                'tags'         => $request->getPost('tags', implode(', ', $article->tags)),
                'preview_text' => $request->getPost('preview_text', $article->preview_text),
                'text'         => $request->getPost('text', $article->text),
            ],
        ];

        $data['fields'] = array_map('trim', $data['fields']);
        $data['fields']['attached_files'] = (array) $request->getPost('attached_files', [], FILTER_VALIDATE_INT);

        $errors = [];
        // Processing the sent data from the form.
        if ($request->getMethod() === 'POST') {
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
                    $data['fields']['updated_by'] = $user->id;
                    $data['fields']['attached_files'] = array_merge((array) $article->attached_files, $data['fields']['attached_files']);
                    $article->update($data['fields']);

                    $search_text = $data['fields']['name'] . strip_tags(' ' . $data['fields']['preview_text'] . ' ' . $data['fields']['text']);
                    (new NewsSearchIndex())->updateOrCreate(
                        ['article_id' => $article->id],
                        ['text' => $search_text]
                    );
                    $_SESSION['success_message'] = __('The article was updated successfully');
                    header('Location: /admin/news/content/' . $article->section_id . '/');
                    exit;
                }
                $errors[] = __('An article with this code already exists');
            }
        }

        $data['errors'] = $errors;

        return $this->render->render('news::admin/add_article', ['data' => $data]);
    }

    /**
     * Delete section
     *
     * @param int $article_id
     * @param Request $request
     * @param FileStorage $storage
     */
    public function del(int $article_id, Request $request, FileStorage $storage): void
    {
        $data = [];
        // Get the section to delete
        try {
            $article = (new NewsArticle())->findOrFail($article_id);
        } catch (ModelNotFoundException $exception) {
            exit($exception->getMessage());
        }

        $post = $request->getParsedBody();

        // Checking the data and deleting the section
        if (
            isset($post['delete_token'], $_SESSION['delete_token']) &&
            $_SESSION['delete_token'] === $post['delete_token'] &&
            $request->getMethod() === 'POST'
        ) {
            // Delete article
            try {
                if (! empty($article->attached_files)) {
                    foreach ($article->attached_files as $attached_file) {
                        try {
                            $storage->delete($attached_file);
                        } catch (Exception | FilesystemException $exception) {
                        }
                    }
                }
                $article->delete();
            } catch (\Exception $exception) {
                exit($exception->getMessage());
            }

            $_SESSION['success_message'] = __('The article was successfully deleted');
            header('Location: /admin/news/content/' . $article->section_id);
            exit;
        }

        $data['article'] = $article;

        // Generate the token
        $data['delete_token'] = uniqid('', true);
        $_SESSION['delete_token'] = $data['delete_token'];

        $data['action_url'] = '/admin/news/del_article/' . $article_id;

        echo $this->render->render('news::admin/del', ['data' => $data]);
    }

    public function loadFile(Request $request): string
    {
        try {
            /** @var UploadedFile[] $files */
            $files = $request->getUploadedFiles();
            $file_info = new FileInfo($files['upload']->getClientFilename());
            if (! $file_info->isImage()) {
                return json_encode(
                    [
                        'error' => [
                            'message' => __('Only images are allowed'),
                        ],
                    ]
                );
            }

            $file = (new FileStorage())->saveFromRequest('upload', 'news');
            $file_array = [
                'id'       => $file->id,
                'name'     => $file->name,
                'uploaded' => 1,
                'url'      => $file->url,
            ];
            header('Content-Type: application/json');
            return json_encode($file_array);
        } catch (FilesystemException | Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            return json_encode(['errors' => $e->getMessage()]);
        }
    }
}
