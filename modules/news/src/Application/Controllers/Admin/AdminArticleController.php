<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Controllers\Admin;

use Carbon\Carbon;
use Exception;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use Johncms\FileInfo;
use Johncms\Files\FileStorage;
use Johncms\Http\Controller\AdminControllerContext;
use Johncms\Modules\News\Application\Utils\Helpers;
use Johncms\Modules\News\Domain\Models\NewsArticle;
use Johncms\Modules\News\Domain\Models\NewsSearchIndex;
use Johncms\Modules\News\Domain\Models\NewsSection;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\System\Utility\EditorContentNormalizer;
use Johncms\System\View\Render;
use Johncms\Users\User;
use League\Flysystem\FilesystemException;

final readonly class AdminArticleController
{
    public function __construct(
        private AdminControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private EditorContentNormalizer $editorContentNormalizer,
    ) {
        $this->controllerContext->initModule('news');
        $this->navChain->add(__('News'), '/admin/news/');
        $this->render->addData(
            [
                'title'       => __('News'),
                'page_title'  => __('News'),
                'module_menu' => ['news' => true],
            ]
        );
        $this->navChain->add(__('Section list'), '/admin/news/content/');
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
                $this->navChain->add($current_section->name, '/admin/news/content/' . $current_section->id);
            } catch (ModelNotFoundException $exception) {
                pageNotFound();
            }
        }

        $this->navChain->add(__('Add article'));

        $data = [
            'action_url' => '/admin/news/add_article/' . $section_id,
            'back_url'   => '/admin/news/content/' . $section_id,
            'section_id' => $section_id,
            'fields'     => [
                'active'       => $request->bodyInt('active', 1),
                'section_id'   => $section_id,
                'active_from'  => htmlspecialchars($request->body('active_from', '')),
                'active_to'    => htmlspecialchars($request->body('active_to', '')),
                'name'         => $request->body('name', ''),
                'page_title'   => $request->body('page_title', ''),
                'code'         => $request->body('code', ''),
                'keywords'     => $request->body('keywords', ''),
                'description'  => $request->body('description', ''),
                'tags'         => $request->body('tags', ''),
                'preview_text' => $request->body('preview_text', ''),
                'text'         => $request->body('text', ''),
            ],
        ];

        $data['fields'] = array_map('trim', $data['fields']);
        $data['fields']['attached_files'] = (array) $request->bodyInts('attached_files');

        $errors = [];
        // Processing the sent data from the form.
        if ($request->getMethod() === 'POST') {
            $data['fields']['preview_text'] = $this->editorContentNormalizer->trimEdgeEmptyBlocks($data['fields']['preview_text']);
            $data['fields']['text'] = $this->editorContentNormalizer->trimEdgeEmptyBlocks($data['fields']['text']);

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
        $this->navChain->add($article->name);

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
                'active'       => $request->bodyInt('active', (int) $article->active),
                'active_from'  => htmlspecialchars($request->body('active_from', $active_from ?? '')),
                'active_to'    => htmlspecialchars($request->body('active_to', $active_to ?? '')),
                'name'         => $request->body('name', (string) $article->name),
                'page_title'   => $request->body('page_title', (string) $article->page_title),
                'code'         => $request->body('code', (string) $article->code),
                'keywords'     => $request->body('keywords', (string) $article->keywords),
                'description'  => $request->body('description', (string) $article->description),
                'tags'         => $request->body('tags', implode(', ', $article->tags)),
                'preview_text' => $request->body('preview_text', (string) $article->preview_text),
                'text'         => $request->body('text', (string) $article->text),
            ],
        ];

        $data['fields'] = array_map('trim', $data['fields']);
        $data['fields']['attached_files'] = (array) $request->bodyInts('attached_files');

        $errors = [];
        // Processing the sent data from the form.
        if ($request->getMethod() === 'POST') {
            $data['fields']['preview_text'] = $this->editorContentNormalizer->trimEdgeEmptyBlocks($data['fields']['preview_text']);
            $data['fields']['text'] = $this->editorContentNormalizer->trimEdgeEmptyBlocks($data['fields']['text']);

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

        $post = $request->request->all();

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
            $files = $request->files->all();
            $file_info = new FileInfo($files['upload']->getClientOriginalName());
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
