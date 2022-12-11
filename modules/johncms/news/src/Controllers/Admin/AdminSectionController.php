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

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Johncms\Controller\BaseAdminController;
use Johncms\Http\Request;
use Johncms\Http\Response\RedirectResponse;
use Johncms\Http\Session;
use Johncms\News\Models\NewsSection;
use Johncms\News\Section;
use Johncms\News\Utils\Helpers;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class AdminSectionController extends BaseAdminController
{
    protected string $moduleName = 'johncms/news';

    protected array $config;

    public function __construct()
    {
        parent::__construct();
        $this->config = di('config')['news'] ?? [];
        $this->metaTagManager->setAll(__('News'));
        $this->render->addData(['module_menu' => ['news' => true]]);
        $this->navChain->add(__('News'), route('news.admin.index'));
        $this->navChain->add(__('Section list'), route('news.admin.section'));
    }

    /**
     * Section creation page
     *
     * @param Request $request
     * @param Section $section_service
     * @param Session $session
     * @param int $section_id
     * @return ResponseInterface|string
     * @throws Throwable
     */
    public function add(Request $request, Section $section_service, Session $session, int $section_id = 0): ResponseInterface|string
    {
        $this->metaTagManager->setAll(__('Create section'));
        if (! empty($section_id)) {
            $currentSection = (new NewsSection())->findOrFail($section_id);
            Helpers::buildAdminBreadcrumbs($currentSection->parentSection);

            // Adding the current section to the navigation chain
            $this->navChain->add($currentSection->name, route('news.admin.section', ['section_id' => $currentSection->id]));
        }
        $this->navChain->add(__('Create section'));

        $data = [
            'action_url' => route('news.admin.sections.add_store', ['section_id' => $section_id]),
            'back_url'   => route('news.admin.section', ['section_id' => $section_id]),
            'section_id' => $section_id,
            'fields'     => [
                'parent'      => $section_id,
                'name'        => $request->getPost('name', ''),
                'code'        => $request->getPost('code', ''),
                'keywords'    => $request->getPost('keywords', ''),
                'description' => $request->getPost('description', ''),
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
                $check = (new NewsSection())
                    ->where('code', $data['fields']['code'])
                    ->when(empty($section_id), function (Builder $builder) {
                        return $builder->whereNull('parent')->orWhere('parent', 0);
                    })
                    ->when(! empty($section_id), function (Builder $builder) use ($section_id) {
                        return $builder->where('parent', $section_id);
                    })
                    ->first();

                if (! $check) {
                    (new NewsSection())->create($data['fields']);
                    $section_service->clearCache();
                    $session->flash('success_message', __('The section was created successfully'));
                    return new RedirectResponse(route('news.admin.section', ['section_id' => $section_id]));
                }
                $errors[] = __('A section with this code already exists');
            }
        }

        $data['errors'] = $errors;

        return $this->render->render('johncms/news::admin/add_section', ['data' => $data]);
    }

    /**
     * The edit section page
     *
     * @param int $section_id
     * @param Request $request
     * @param Session $session
     * @return \Johncms\Http\Response\RedirectResponse|string
     * @throws Throwable
     */
    public function edit(int $section_id, Request $request, Session $session): RedirectResponse|string
    {
        $this->metaTagManager->setAll(__('Edit section'));
        $section = (new NewsSection())->findOrFail($section_id);
        Helpers::buildAdminBreadcrumbs($section->parentSection);
        $this->navChain->add(__('Edit section'));

        $data = [
            'action_url' => route('news.admin.sections.edit_store', ['section_id' => $section_id]),
            'back_url'   => route('news.admin.section', ['section_id' => $section->parent]),
            'section_id' => $section_id,
            'fields'     => [
                'name'        => $request->getPost('name', $section->name),
                'code'        => $request->getPost('code', $section->code),
                'keywords'    => $request->getPost('keywords', $section->keywords),
                'description' => $request->getPost('description', $section->description),
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
                    $session->flash('success_message', __('The section was updated successfully'));
                    return new RedirectResponse(route('news.admin.section', ['section_id' => $section->parent]));
                }
                $errors[] = __('A section with this code already exists');
            }
        }

        $data['errors'] = $errors;

        return $this->render->render('johncms/news::admin/add_section', ['data' => $data]);
    }

    /**
     * Delete section
     *
     * @param int $section_id
     * @param Request $request
     * @param Section $sectionService
     * @param Session $session
     * @return ResponseInterface|string
     * @throws Throwable
     */
    public function del(int $section_id, Request $request, Section $sectionService, Session $session): ResponseInterface|string
    {
        // Get the section to delete
        $section = (new NewsSection())->findOrFail($section_id);

        // Checking the data and deleting the section
        if ($request->getMethod() === 'POST') {
            $sectionService->delete($section);
            $session->flash('success_message', __('The section was successfully deleted'));
            return new RedirectResponse(route('news.admin.section', ['section_id' => $section->parent]));
        }

        return $this->render->render('johncms/news::admin/del', [
            'data' => [
                'section'    => $section,
                'action_url' => route('news.admin.sections.del_store', ['section_id' => $section_id]),
            ],
        ]);
    }
}
