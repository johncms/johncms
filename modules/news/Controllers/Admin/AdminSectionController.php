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

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use Johncms\Controller\BaseController;
use Johncms\System\Http\Request;
use News\Models\NewsArticle;
use News\Models\NewsSection;
use News\Utils\Helpers;
use News\Utils\Subsections;

class AdminSectionController extends BaseController
{
    protected $module_name = 'news';

    protected $config;

    public function __construct()
    {
        parent::__construct();
        $this->config = di('config')['news'] ?? [];
        $this->nav_chain->add(__('News'), '/news/');
        $this->nav_chain->add(__('Admin panel'), '/news/admin/');
        $this->nav_chain->add(__('Section list'), '/news/admin/content/');
    }

    /**
     * Section creation page
     *
     * @param Request $request
     * @param int $section_id
     * @return string
     */
    public function add(Request $request, int $section_id = 0): string
    {
        $this->render->addData(
            [
                'title'      => __('Create section'),
                'page_title' => __('Create section'),
            ]
        );

        if (! empty($section_id)) {
            try {
                $current_section = (new NewsSection())->findOrFail($section_id);

                Helpers::buildAdminBreadcrumbs($current_section->parentSection);

                // Adding the current section to the navigation chain
                $this->nav_chain->add($current_section->name, '/news/admin/content/' . $current_section->id);
            } catch (ModelNotFoundException $exception) {
                pageNotFound();
            }
        }

        $this->nav_chain->add(__('Create section'));

        $data = [
            'action_url' => '/news/admin/add_section/' . $section_id,
            'back_url'   => '/news/admin/content/' . $section_id,
            'section_id' => $section_id,
            'fields'     => [
                'parent'      => $section_id,
                'name'        => $request->getPost('name', '', FILTER_SANITIZE_STRING),
                'code'        => $request->getPost('code', '', FILTER_SANITIZE_STRING),
                'keywords'    => $request->getPost('keywords', '', FILTER_SANITIZE_STRING),
                'description' => $request->getPost('description', '', FILTER_SANITIZE_STRING),
                'text'        => $request->getPost('text', ''),
            ],
        ];

        $data['fields'] = array_map('trim', $data['fields']);

        $errors = [];
        // Processing the sent data from the form.
        if ($request->getMethod() === 'POST') {
            if (empty($data['fields']['name'])) {
                $errors[] = __('The section name cannot be empty');
            }

            // Code generation
            if (empty($data['fields']['code'])) {
                $data['fields']['code'] = Str::slug($data['fields']['name']);
            } else {
                $data['fields']['code'] = Str::slug($data['fields']['code']);
            }

            if (empty($errors)) {
                if (! empty($section_id)) {
                    $check = (new NewsSection())
                        ->where('code', $data['fields']['code'])
                        ->where('parent', $section_id)
                        ->first();
                } else {
                    $check = (new NewsSection())
                        ->where('code', $data['fields']['code'])
                        ->whereNull('parent')
                        ->first();
                }

                if (! $check) {
                    (new NewsSection())->create($data['fields']);
                    $_SESSION['success_message'] = __('The section was created successfully');
                    header('Location: /news/admin/content/' . $section_id);
                    exit;
                }
                $errors[] = __('A section with this code already exists');
            }
        }

        $data['errors'] = $errors;

        return $this->render->render('news::admin/add_section', ['data' => $data]);
    }

    /**
     * The edit section page
     *
     * @param int $section_id
     * @param Request $request
     * @return string
     */
    public function edit(int $section_id, Request $request): string
    {
        $this->nav_chain->add(__('Edit section'));
        $this->render->addData(
            [
                'title'      => __('Edit section'),
                'page_title' => __('Edit section'),
            ]
        );

        try {
            $section = (new NewsSection())->findOrFail($section_id);
            Helpers::buildAdminBreadcrumbs($section->parentSection);
        } catch (ModelNotFoundException $exception) {
            pageNotFound();
        }

        $data = [
            'action_url' => '/news/admin/edit_section/' . $section_id,
            'back_url'   => '/news/admin/content/' . $section->parent,
            'section_id' => $section_id,
            'fields'     => [
                'name'        => $request->getPost('name', $section->name, FILTER_SANITIZE_STRING),
                'code'        => $request->getPost('code', $section->code, FILTER_SANITIZE_STRING),
                'keywords'    => $request->getPost('keywords', $section->keywords, FILTER_SANITIZE_STRING),
                'description' => $request->getPost('description', $section->description, FILTER_SANITIZE_STRING),
                'text'        => $request->getPost('text', $section->text),
            ],
        ];

        $data['fields'] = array_map('trim', $data['fields']);

        $errors = [];
        // Processing the sent data from the form.
        if ($request->getMethod() === 'POST') {
            if (empty($data['fields']['name'])) {
                $errors[] = __('The section name cannot be empty');
            }

            // Code generation
            if (empty($data['fields']['code'])) {
                $data['fields']['code'] = Str::slug($data['fields']['name']);
            } else {
                $data['fields']['code'] = Str::slug($data['fields']['code']);
            }

            if (empty($errors)) {
                $check = (new NewsSection())
                    ->where('code', $data['fields']['code'])
                    ->where('id', '!=', $section_id)
                    ->where('parent', '=', $section->parent)
                    ->first();

                if (! $check) {
                    $section->update($data['fields']);
                    $_SESSION['success_message'] = __('The section was updated successfully');
                    header('Location: /news/admin/content/' . $section->parent);
                    exit;
                }
                $errors[] = __('A section with this code already exists');
            }
        }

        $data['errors'] = $errors;

        return $this->render->render('news::admin/add_section', ['data' => $data]);
    }

    /**
     * Delete section
     *
     * @param int $section_id
     * @param Request $request
     * @return string
     */
    public function del(int $section_id, Request $request): string
    {
        $data = [];
        // Get the section to delete
        try {
            $section = (new NewsSection())->findOrFail($section_id);
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
            /** @var Subsections $subsections */
            $subsections = di(Subsections::class);
            $children_sections = $subsections->getIds($section);
            $children_sections[] = $section->id;

            $subsections->clearCache();

            // Delete articles
            (new NewsArticle())->whereIn('section_id', $children_sections)->delete();

            // Delete subsections
            (new NewsSection())->whereIn('id', $children_sections)->delete();

            $_SESSION['success_message'] = __('The section was successfully deleted');
            header('Location: /news/admin/content/' . $section->parent);
            exit;
        }

        $data['section'] = $section;

        // Generate the token
        $data['delete_token'] = uniqid('', true);
        $_SESSION['delete_token'] = $data['delete_token'];

        $data['action_url'] = '/news/admin/del_section/' . $section_id;

        return $this->render->render('news::admin/del', ['data' => $data]);
    }
}
