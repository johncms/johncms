<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\News\Controllers\Admin;

use Carbon\Carbon;
use Exception;
use GuzzleHttp\Psr7\UploadedFile;
use Illuminate\Support\Str;
use Johncms\Controller\BaseAdminController;
use Johncms\FileInfo;
use Johncms\Files\FileStorage;
use Johncms\Http\Request;
use Johncms\Http\Response\RedirectResponse;
use Johncms\Http\Session;
use Johncms\News\Models\NewsArticle;
use Johncms\News\Models\NewsSearchIndex;
use Johncms\News\Models\NewsSection;
use Johncms\News\Utils\Helpers;
use Johncms\Users\User;
use League\Flysystem\FilesystemException;
use Throwable;

class AdminArticleController extends BaseAdminController
{
    protected string $moduleName = 'johncms/news';

    protected array $config;

    public function __construct()
    {
        parent::__construct();
        $this->config = di('config')['news'] ?? [];
        $this->navChain->add(__('News'), route('news.admin.index'));
        $this->metaTagManager->setAll(__('News'));
        $this->render->addData(['module_menu' => ['news' => true]]);
        $this->navChain->add(__('Section list'), route('news.admin.section'));
    }

    /**
     * Article creation page
     *
     * @param Request $request
     * @param User $user
     * @param Session $session
     * @param int $section_id
     * @return RedirectResponse|string
     * @throws Throwable
     */
    public function add(Request $request, User $user, Session $session, int $section_id = 0): RedirectResponse|string
    {
        $this->metaTagManager->setAll(__('Add article'));

        if (! empty($section_id)) {
            $current_section = (new NewsSection())->findOrFail($section_id);

            Helpers::buildAdminBreadcrumbs($current_section->parentSection);

            // Adding the current section to the navigation chain
            $this->navChain->add($current_section->name, route('news.admin.section', ['section_id' => $current_section->id]));
        }

        $this->navChain->add(__('Add article'));

        $data = [
            'action_url' => route('news.admin.article.addStore', ['section_id' => $section_id]),
            'back_url'   => route('news.admin.section', ['section_id' => $section_id]),
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
                    $session->flash('success_message', __('The article was created successfully'));
                    return new RedirectResponse(route('news.admin.section', ['section_id' => $section_id]));
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
     * @param Session $session
     * @return RedirectResponse|string
     * @throws Throwable
     */
    public function edit(int $article_id, Request $request, User $user, Session $session): RedirectResponse|string
    {
        $this->metaTagManager->setAll(__('Edit article'));

        $article = (new NewsArticle())->findOrFail($article_id);

        Helpers::buildAdminBreadcrumbs($article->parentSection);
        $this->navChain->add($article->name);

        if (! empty($article->getRawOriginal('active_from'))) {
            $active_from = Carbon::parse($article->getRawOriginal('active_from'))->format('d.m.Y H:i');
        }
        if (! empty($article->getRawOriginal('active_to'))) {
            $active_to = Carbon::parse($article->getRawOriginal('active_to'))->format('d.m.Y H:i');
        }

        $data = [
            'action_url' => route('news.admin.article.editStore', ['article_id' => $article->id]),
            'back_url'   => route('news.admin.section', ['section_id' => $article->section_id]),
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
                    $session->flash('success_message', __('The article was updated successfully'));
                    return new RedirectResponse(route('news.admin.section', ['section_id' => $article->section_id]));
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
     * @param Session $session
     * @return RedirectResponse|string
     * @throws Throwable
     */
    public function del(int $article_id, Request $request, FileStorage $storage, Session $session): RedirectResponse|string
    {
        $data = [];
        // Get the section to delete
        $article = (new NewsArticle())->findOrFail($article_id);

        // Checking the data and deleting the section
        if ($request->getMethod() === 'POST') {
            // Delete article
            try {
                if (! empty($article->attached_files)) {
                    foreach ($article->attached_files as $attached_file) {
                        try {
                            $storage->delete($attached_file);
                        } catch (Exception | FilesystemException) {
                        }
                    }
                }
                $article->delete();
            } catch (Exception $exception) {
                exit($exception->getMessage());
            }

            $session->flash('success_message', __('The article was successfully deleted'));
            return new RedirectResponse(route('news.admin.section', ['section_id' => $article->section_id]));
        }

        $data['article'] = $article;
        $data['action_url'] = route('news.admin.article.delStore', ['article_id' => $article_id]);

        return $this->render->render('news::admin/del', ['data' => $data]);
    }

    public function loadFile(Request $request): array|string
    {
        try {
            /** @var UploadedFile[] $files */
            $files = $request->getUploadedFiles();
            $file_info = new FileInfo($files['upload']->getClientFilename());
            if (! $file_info->isImage()) {
                return [
                    'error' => [
                        'message' => __('Only images are allowed'),
                    ],
                ];
            }

            $file = (new FileStorage())->saveFromRequest('upload', 'news');
            return [
                'id'       => $file->id,
                'name'     => $file->name,
                'uploaded' => 1,
                'url'      => $file->url,
            ];
        } catch (FilesystemException | Exception $e) {
            return [
                'error' => [
                    'message' => $e->getMessage(),
                ],
            ];
        }
    }
}
