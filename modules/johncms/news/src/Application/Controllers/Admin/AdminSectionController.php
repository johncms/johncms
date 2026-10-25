<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Controllers\Admin;

use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\News\Application\DTO\SectionFormDTO;
use Johncms\Modules\News\Application\Exceptions\SectionFormException;
use Johncms\Modules\News\Application\Exceptions\SectionNotFoundException;
use Johncms\Modules\News\Application\UseCases\GetSectionTreeUseCase;
use Johncms\Modules\News\Application\UseCases\GetSectionUseCase;
use Johncms\Modules\News\Application\UseCases\SaveSectionUseCase;
use Johncms\Modules\News\Application\Utils\Helpers;
use Johncms\NavChain;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class AdminSectionController
{
    public function __construct(
        private NavChain $navChain,
        private GetSectionUseCase $getSection,
        private GetSectionTreeUseCase $getTree,
        private SaveSectionUseCase $saveSection,
        private Session $session,
    ) {
    }

    /**
     * The links every page of the news section sits under. Added by the page rather than by
     * the constructor: the chain belongs to the request being served.
     */
    private function addSectionBreadcrumbs(): void
    {
        $this->navChain->add(__('News'), '/admin/news');
    }

    /**
     * Adding a section under the section of the address.
     */
    public function add(Request $request, int $section_id = 0): Response | ViewResponse
    {
        if ($section_id !== 0 && ! $this->getTree->execute()->has($section_id)) {
            pageNotFound();
        }

        $form = $this->form($request, $section_id);
        $errors = [];

        if ($request->getMethod() === 'POST') {
            try {
                $this->saveSection->create($form);
                $this->session->flash('success_message', __('The section was created successfully'));

                return new RedirectResponse('/admin/news/content/' . $section_id);
            } catch (SectionFormException $e) {
                $errors = $e->errors;
            }
        }

        $this->addSectionBreadcrumbs();
        $this->navChain->add(__('Create section'));

        return new ViewResponse('@news/admin/section-form.twig', $this->menu(__('Create section')) + [
            'action_url' => '/admin/news/add_section/' . $section_id,
            'back_url'   => '/admin/news/content/' . $section_id,
            'section_id' => $section_id,
            'fields'     => $this->fields($form),
            'errors'     => array_merge(...array_values($errors)),
        ]);
    }

    /**
     * Changing a section where it stands.
     */
    public function edit(int $section_id, Request $request): Response | ViewResponse
    {
        try {
            $section = $this->getSection->execute($section_id);
        } catch (SectionNotFoundException) {
            pageNotFound();
        }

        $parentId = (int) $section->parent;
        $form = $request->getMethod() === 'POST'
            ? $this->form($request, $parentId)
            : new SectionFormDTO($parentId, $section->name, $section->code, (string) $section->keywords, (string) $section->description, (string) $section->text);
        $errors = [];

        if ($request->getMethod() === 'POST') {
            try {
                $this->saveSection->update($section, $form);
                $this->session->flash('success_message', __('The section was updated successfully'));

                return new RedirectResponse('/admin/news/content/' . $parentId);
            } catch (SectionFormException $e) {
                $errors = $e->errors;
            }
        }

        $this->addSectionBreadcrumbs();
        Helpers::buildAdminBreadcrumbs($section->parentSection);
        $this->navChain->add(__('Edit section'));

        return new ViewResponse('@news/admin/section-form.twig', $this->menu(__('Edit section')) + [
            'action_url' => '/admin/news/edit_section/' . $section_id,
            'back_url'   => '/admin/news/content/' . $parentId,
            'section_id' => $section_id,
            'fields'     => $this->fields($form),
            'errors'     => array_merge(...array_values($errors)),
        ]);
    }

    private function form(Request $request, int $parentId): SectionFormDTO
    {
        return new SectionFormDTO(
            parentId: $parentId,
            name: $request->body('name'),
            code: $request->body('code'),
            keywords: $request->body('keywords'),
            description: $request->body('description'),
            text: $request->body('text'),
        );
    }

    /**
     * @return array<string, string>
     */
    private function fields(SectionFormDTO $form): array
    {
        return [
            'name'        => $form->name,
            'code'        => $form->code,
            'keywords'    => $form->keywords,
            'description' => $form->description,
            'text'        => $form->text,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function menu(string $title): array
    {
        return [
            'title'       => $title,
            'page_title'  => $title,
            'module_menu' => ['news' => true],
        ];
    }
}
