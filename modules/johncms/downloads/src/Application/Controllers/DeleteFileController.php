<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Controllers;

use Johncms\Modules\Downloads\Application\Services\DownloadCategoryPathService;
use Johncms\Modules\Downloads\Application\Exceptions\FileNotFoundException;
use Johncms\Modules\Downloads\Application\UseCases\DeleteFileUseCase;
use Johncms\Modules\Downloads\Domain\Models\DownloadFile;
use Johncms\Http\View\ViewResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class DeleteFileController
{
    public function __construct(
        private DeleteFileUseCase $deleteFileUseCase,
        private DownloadCategoryPathService $categoryPathService,
    ) {
    }

    /** Carried out only from a confirmed form (RequireConfirmationMiddleware). */
    public function __invoke(int $id): RedirectResponse|ViewResponse
    {
        $refid = (int) (DownloadFile::query()->select('refid')->find($id)?->refid ?? 0);

        try {
            $this->deleteFileUseCase->execute($id);
        } catch (FileNotFoundException) {
            return $this->notFound();
        }

        $redirectUrl = $refid > 0
            ? ($this->categoryPathService->getCategoryUrlById($refid) ?? '/downloads/')
            : '/downloads/';

        return new RedirectResponse($redirectUrl);
    }

    private function notFound(): ViewResponse
    {
        return new ViewResponse('@theme/pages/result.twig', [
            'title'         => __('File not found'),
            'type'          => 'alert-danger',
            'message'       => __('File not found'),
            'back_url'      => '/downloads/',
            'back_url_name' => __('Downloads'),
        ], Response::HTTP_NOT_FOUND);
    }
}
