<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Controllers\Admin;

use Johncms\Modules\Collections\Application\Api\CollectionsPreviewApiInterface;
use Johncms\Modules\Collections\Application\Exceptions\CollectionPreviewAccessDeniedException;
use Johncms\Modules\Collections\Application\UseCases\GetPublicItemUseCase;
use Johncms\View\RendererInterface;
use Symfony\Component\HttpFoundation\Response;

final readonly class CollectionItemPreviewController
{
    public function __construct(
        private CollectionsPreviewApiInterface $preview,
        private GetPublicItemUseCase $detail,
        private RendererInterface $renderer,
    ) {
    }

    public function __invoke(int $collection_id, int $id): Response
    {
        try {
            $item = $this->preview->findItem($collection_id, $id);
        } catch (CollectionPreviewAccessDeniedException) {
            return new Response('', Response::HTTP_FORBIDDEN);
        }
        if ($item === null) {
            pageNotFound();
        }
        return new Response($this->renderer->render('@collections/admin/item-preview.twig', [
            'title' => $item->name, 'page_title' => $item->name,
            'module_menu' => ['collections' => true],
            'item' => $this->detail->format($item),
            'back_url' => '/admin/collections/' . $collection_id . '/items/' . $id . '/edit',
        ]), headers: ['Cache-Control' => 'private, no-store', 'X-Robots-Tag' => 'noindex, nofollow']);
    }
}
