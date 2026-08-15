<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Controllers;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Library\Application\Services\LibraryCategoryPathService;
use Johncms\Modules\Library\Application\Services\LibraryPermissions;
use Johncms\Modules\Library\Application\Services\LibrarySlugService;
use Johncms\Modules\Library\Domain\Models\LibraryCategory;
use Johncms\NavChain;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\Response;

final readonly class CreateSectionController
{
    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private NavChain $navChain,
        private User $currentUser,
        private LibrarySlugService $slugService,
        private LibraryCategoryPathService $categoryPathService,
    ) {
    }

    public function __invoke(Request $request): ViewResponse
    {
        if (! $this->accessChecker->allows(LibraryPermissions::MODERATE)) {
            return new ViewResponse(
                '@theme/pages/result.twig',
                [
                    'title'         => __('Create Section'),
                    'type'          => 'alert-danger',
                    'message'       => __('Access denied'),
                    'back_url'      => '/library/',
                    'back_url_name' => __('Library'),
                ],
                Response::HTTP_FORBIDDEN
            );
        }

        $parentId = max(0, $request->queryInt('id', 0));
        $formUrl = '/library/section/create' . ($parentId ? '?id=' . $parentId : '');

        $this->navChain->add(__('Library'), '/library/');
        $this->navChain->add(__('Create Section'));

        if ($request->getMethod() === 'POST') {
            return $this->handlePost($request, $parentId, $formUrl);
        }

        return $this->renderForm($parentId, $formUrl, false);
    }

    private function handlePost(Request $request, int $parentId, string $formUrl): ViewResponse
    {
        $post = $request->request->all();
        $name = trim((string) ($post['name'] ?? ''));

        if (empty($name)) {
            return new ViewResponse('@theme/pages/result.twig', [
                'title'         => __('Create Section'),
                'type'          => 'alert-danger',
                'message'       => __('You have not entered the name'),
                'back_url'      => $formUrl,
                'back_url_name' => __('Repeat'),
            ]);
        }

        $pos  = (int) LibraryCategory::query()->max('id') + 1;
        $slug = $this->slugService->generateCategorySlug($name, $parentId);

        LibraryCategory::query()->create([
            'parent'      => $parentId,
            'name'        => $name,
            'slug'        => $slug,
            'description' => trim((string) ($post['description'] ?? '')),
            'dir'         => (int) ($post['type'] ?? 0),
            'pos'         => $pos,
        ]);

        return $this->renderForm($parentId, $formUrl, true);
    }

    private function renderForm(int $parentId, string $formUrl, bool $created): ViewResponse
    {
        $parentUrl = $parentId > 0
            ? $this->categoryPathService->getCategoryUrlById($parentId) ?? '/library/'
            : '/library/';

        return new ViewResponse('@library/public/create-section.twig', [
            'title'        => __('Create Section'),
            'page_title'   => __('Create Section'),
            'form_url'     => $formUrl,
            'parent_url'   => $parentUrl,
            'created'      => $created,
            'field_height' => $this->currentUser->config->fieldHeight,
        ]);
    }
}
