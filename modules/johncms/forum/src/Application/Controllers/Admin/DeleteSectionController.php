<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers\Admin;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Forum\Application\Exceptions\SectionDeletionException;
use Johncms\Modules\Forum\Application\Services\ForumPermissions;
use Johncms\Modules\Forum\Application\UseCases\DeleteForumSectionUseCase;
use Johncms\Modules\Forum\Domain\Models\ForumSection;
use Johncms\Modules\Forum\Domain\Repository\ForumStructureRepositoryInterface;
use Johncms\Modules\Forum\Infrastructure\Storage\ForumAttachmentStorage;
use Johncms\NavChain;

/**
 * Deleting a section of the forum. The confirmation is a dialog that says what the section holds
 * and asks where it goes: the modal of the panel asks for it with X-Requested-With and gets the
 * dialog alone, a plain visit gets it as a page, and so does a choice that was not accepted.
 */
final readonly class DeleteSectionController
{
    private const string LIST_URL = '/admin/forum';

    public function __construct(
        private NavChain $navChain,
        private AccessCheckerInterface $accessChecker,
        private ForumStructureRepositoryInterface $repository,
        private DeleteForumSectionUseCase $deleteSection,
        private ForumAttachmentStorage $attachments,
        private Session $session,
    ) {
    }

    public function index(Request $request, int $id): ViewResponse
    {
        $section = $this->section($id);
        $data = $this->dialog($section, '');

        if ($request->isXmlHttpRequest()) {
            return new ViewResponse('@forum/admin/delete-section-dialog.twig', $data);
        }

        return $this->page($section, $data);
    }

    public function delete(Request $request, int $id): ViewResponse
    {
        $section = $this->section($id);
        $target = $request->body('target');

        try {
            $filenames = $this->deleteSection->execute(
                $section,
                ctype_digit($target) ? (int) $target : null,
                $request->body('mode') === 'destroy',
                $this->accessChecker->allows(ForumPermissions::STRUCTURE_DESTROY),
            );
        } catch (SectionDeletionException $e) {
            return $this->page($section, $this->dialog($section, $e->getMessage()));
        }

        foreach ($filenames as $filename) {
            $this->attachments->delete($filename);
        }

        $this->session->flash('success_message', __('The section was deleted'));
        redirect(self::LIST_URL);
    }

    private function section(int $id): ForumSection
    {
        return $this->repository->find($id) ?? pageNotFound('@admin/pages/errors/404.twig');
    }

    /**
     * @return array<string, mixed>
     */
    private function dialog(ForumSection $section, string $error): array
    {
        $context = $this->deleteSection->context($section);

        return [
            'title'        => $this->title($section),
            'name'         => $section->name,
            'holds_topics' => $context->holdsTopics,
            'sections'     => $context->sections,
            'topics'       => $context->topics,
            'empty'        => $context->isEmpty(),
            'targets'      => $context->targets,
            'can_destroy'  => $context->holdsTopics && $this->accessChecker->allows(ForumPermissions::STRUCTURE_DESTROY),
            'error'        => $error,
            'form_action'  => '/admin/forum/sections/' . $section->id . '/delete',
            'back_url'     => self::LIST_URL,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function page(ForumSection $section, array $data): ViewResponse
    {
        $title = $this->title($section);
        $this->navChain->add(__('Forum'), self::LIST_URL);
        $this->navChain->add($title);

        return new ViewResponse('@forum/admin/delete-section.twig', [
            // The dialog carries the heading itself.
            'title'       => $title,
            'module_menu' => ['forum' => true],
            'dialog'      => $data,
        ]);
    }

    private function title(ForumSection $section): string
    {
        return (int) $section->parent === 0 ? __('Delete category') : __('Delete section');
    }
}
