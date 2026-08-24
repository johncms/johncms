<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Forum\Domain\Repository\ForumFileRepositoryInterface;
use Johncms\Modules\Forum\Infrastructure\Storage\ForumAttachmentStorage;
use Symfony\Component\HttpFoundation\Response;

final readonly class DownloadFileController
{
    public function __construct(
        private ForumFileRepositoryInterface $fileRepository,
        private ForumAttachmentStorage $attachments,
    ) {
    }

    public function __invoke(int $id): ViewResponse
    {
        $file = $this->fileRepository->findById($id);
        if ($file === null) {
            return $this->renderNotFound();
        }

        if (! $this->attachments->exists((string) $file->filename)) {
            return $this->renderNotFound();
        }

        $file->dlcount = (int) $file->dlcount + 1;
        $this->fileRepository->save($file);

        redirect($this->attachments->url((string) $file->filename));
    }

    private function renderNotFound(): ViewResponse
    {
        return new ViewResponse(
            '@theme/pages/result.twig',
            [
                'title'         => __('Download file'),
                'type'          => 'alert-danger',
                'message'       => __('File does not exist'),
                'back_url'      => '/forum/',
                'back_url_name' => __('Forum'),
            ],
            Response::HTTP_NOT_FOUND
        );
    }
}
