<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Controllers\Admin;

use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Collections\Application\Api\CollectionBulkApi;
use Johncms\Modules\Collections\Application\Api\CollectionBulkApiInterface;
use Johncms\Modules\Collections\Application\Exceptions\CollectionDeletionException;
use Johncms\Modules\Collections\Application\Exceptions\CollectionItemFormException;
use Johncms\Modules\Collections\Application\Exceptions\CollectionItemCodeAlreadyExistsException;
use Johncms\Modules\Collections\Application\Services\CollectionWorkspaceMapper;
use Johncms\Modules\Collections\Domain\Exceptions\CollectionWriteConflictException;
use Johncms\NavChain;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface;

final readonly class CollectionBulkAdminController
{
    public function __construct(
        private CollectionBulkApiInterface $bulk,
        private Session $session,
        private NavChain $navChain,
        private CollectionWorkspaceMapper $workspace,
        private ContentCollectionRepositoryInterface $collections,
    ) {
    }

    public function __invoke(Request $request, int $collection_id): ViewResponse
    {
        $back = CollectionWorkspaceMapper::returnUrl($collection_id, $request->body('back'));
        $errors = [];
        try {
            if ($request->body('bulk_started') !== '1' || $request->body('bulk_complete') !== '1') {
                throw new CollectionItemFormException(['ids' => [d__('collections', 'The form was truncated. Nothing was saved. Reduce the number of values or ask the administrator to raise the form input limit.')]]);
            }
            $ids = [];
            foreach ($request->bodyList('ids') as $id) {
                if (! is_string($id) || ! ctype_digit($id) || strlen($id) > 10) {
                    throw new CollectionItemFormException(['ids' => [d__('system', 'Wrong data')]]);
                }
                $ids[] = (int) $id;
            }
            $action = $request->body('bulk_action');
            $target = null;
            if ($action === 'move') {
                $value = $request->body('target_section');
                if ($value !== '' && $value !== '0') {
                    if (! ctype_digit($value) || strlen($value) > 10) {
                        throw new CollectionItemFormException(['section_id' => [d__('collections', 'Choose a section of this collection.')]]);
                    }
                    $target = (int) $value;
                }
            }
            // Leaving out what cannot go is a new selection, so it is confirmed afresh.
            $exclude = $request->body('exclude') === 'blocked';
            if ($request->body('confirmed') === '1' && ! $exclude) {
                try {
                    $count = $this->bulk->execute($collection_id, $ids, $action, $request->body('revision'), $target);
                    $this->session->flash('success_message', sprintf(d__('collections', 'Processed records: %d.'), $count));
                    redirect($back);
                } catch (CollectionDeletionException $exception) {
                    $errors = array_merge(...array_values($exception->errors));
                }
            }
            $preview = $this->bulk->preview($collection_id, $ids, $action, $target);
            $blocked = $preview->relations?->blockedTargets() ?? [];
            if ($exclude && $blocked !== []) {
                $rest = array_values(array_diff($preview->ids, $blocked));
                if ($rest === []) {
                    throw new CollectionItemFormException(['ids' => [d__('collections', 'Every selected record is kept by relations or field defaults.')]]);
                }
                $preview = $this->bulk->preview($collection_id, $rest, $action, $target);
                $blocked = $preview->relations?->blockedTargets() ?? [];
            }
        } catch (CollectionItemFormException | CollectionDeletionException $exception) {
            return $this->error($request, $collection_id, $back, implode(' ', array_merge(...array_values($exception->errors))));
        } catch (CollectionItemCodeAlreadyExistsException) {
            return $this->error($request, $collection_id, $back, d__('collections', 'The destination contains conflicting record codes. Nothing was changed. Resolve the duplicates before moving.'));
        } catch (CollectionWriteConflictException) {
            return $this->error($request, $collection_id, $back, d__('collections', 'The selection changed during processing. Nothing was changed. Open a new confirmation.'));
        }
        $names = array_combine($preview->ids, $preview->names);

        return $this->dialog($request, $collection_id, [
            'title'       => CollectionBulkApi::confirmTitles()[$action],
            'form_action' => CollectionWorkspaceMapper::url($collection_id) . '/bulk',
            'back_url'    => $back,
            'preview'     => $preview,
            'items'       => array_map(static fn (int $id): array => ['name' => $names[$id], 'blocked' => in_array($id, $blocked, true)], $preview->ids),
            'blockers'    => array_map(static fn (array $blocker): array => $blocker + ['name' => $names[$blocker['target']] ?? '#' . $blocker['target']], $preview->relations->blockers ?? []),
            'remaining'   => count($preview->ids) - count($blocked),
            'errors'      => $errors,
        ]);
    }

    /** @param array<string, mixed> $data */
    private function dialog(Request $request, int $collectionId, array $data): ViewResponse
    {
        if ($request->isXmlHttpRequest()) {
            return new ViewResponse('@collections/admin/bulk-dialog.twig', $data);
        }
        $this->navChain->add(d__('collections', 'Collections'), '/admin/collections');
        $collection = $this->collections->findById($collectionId);
        $this->navChain->add($collection !== null ? $collection->name : d__('collections', 'Items'), $data['back_url']);
        $this->navChain->add($data['title']);
        return new ViewResponse('@collections/admin/bulk-confirm.twig', [
            'title' => $data['title'], 'page_title' => $data['title'],
            'module_menu' => $collection !== null ? $this->workspace->menu($collection) : ['collections' => true],
            'dialog' => $data,
        ]);
    }

    private function error(Request $request, int $collectionId, string $back, string $message): ViewResponse
    {
        if ($request->isXmlHttpRequest()) {
            return $this->dialog($request, $collectionId, ['title' => d__('collections', 'Bulk actions'), 'back_url' => $back, 'form_action' => '', 'preview' => null, 'errors' => [$message]]);
        }
        $collection = $this->collections->findById($collectionId);
        return new ViewResponse('@admin/pages/result.twig', [
            'title' => d__('collections', 'Bulk actions'), 'page_title' => d__('collections', 'Bulk actions'),
            'type' => 'alert-danger', 'message' => $message, 'back_url' => $back, 'module_menu' => $collection !== null ? $this->workspace->menu($collection) : ['collections' => true],
        ]);
    }
}
