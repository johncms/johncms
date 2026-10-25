<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Controllers\Admin;

use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\News\Application\Exceptions\SectionNotFoundException;
use Johncms\Modules\News\Application\Services\NewsListControlsMapper;
use Johncms\Modules\News\Application\UseCases\DeleteSectionUseCase;
use Johncms\Modules\News\Application\UseCases\GetSectionDeletionContextUseCase;
use Johncms\NavChain;

/**
 * Deleting a section with everything under it. The confirmation is a dialog that says what goes
 * with the section: the modal of the panel asks for it with X-Requested-With and gets the dialog
 * alone, a plain visit gets it as a page.
 *
 * The list sends where it was in "back", so the form returns to the same page of the same filter.
 */
final readonly class DeleteSectionController
{
    public function __construct(
        private NavChain $navChain,
        private GetSectionDeletionContextUseCase $getContext,
        private DeleteSectionUseCase $deleteSection,
        private NewsListControlsMapper $controls,
        private Session $session,
    ) {
    }

    public function index(Request $request, int $id): ViewResponse
    {
        try {
            $context = $this->getContext->execute($id);
        } catch (SectionNotFoundException) {
            pageNotFound('@admin/pages/errors/404.twig');
        }

        $data = [
            'name'        => $context->section->name,
            'subsections' => $context->subsections,
            'articles'    => $context->articles,
            'form_action' => '/admin/news/sections/' . $context->section->id . '/delete',
            'back_url'    => $this->controls->returnUrl($request->queryParam('back')),
        ];

        if ($request->isXmlHttpRequest()) {
            return new ViewResponse('@news/admin/delete-section-dialog.twig', $data);
        }

        $title = __('Delete section');
        $this->navChain->add(__('News'), NewsListControlsMapper::PATH);
        $this->navChain->add($title);

        return new ViewResponse('@news/admin/delete-section.twig', [
            // The dialog carries the heading itself.
            'title'       => $title,
            'module_menu' => ['news' => true],
            'dialog'      => $data,
        ]);
    }

    public function delete(Request $request, int $id): ViewResponse
    {
        try {
            $this->deleteSection->execute($id);
        } catch (SectionNotFoundException) {
            pageNotFound('@admin/pages/errors/404.twig');
        }

        $this->session->flash('success_message', __('The section was successfully deleted'));

        redirect($this->controls->returnUrl($request->body('back')));
    }
}
