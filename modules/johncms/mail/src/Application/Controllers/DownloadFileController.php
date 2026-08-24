<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\Controllers;

use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Mail\Application\Exceptions\MessageNotFoundException;
use Johncms\Modules\Mail\Application\UseCases\DownloadFileUseCase;
use Symfony\Component\HttpFoundation\RedirectResponse;

final readonly class DownloadFileController
{
    public function __construct(
        private DownloadFileUseCase $downloadFileUseCase,
    ) {
    }

    public function __invoke(int $id): RedirectResponse|ViewResponse
    {
        try {
            $fileUrl = $this->downloadFileUseCase->execute($id);
        } catch (MessageNotFoundException) {
            return new ViewResponse(
                '@theme/pages/result.twig',
                [
                    'title'    => __('Mail'),
                    'type'     => 'alert-danger',
                    'message'  => __('Such file does not exist'),
                    'back_url' => '/mail/files',
                ]
            );
        }

        return new RedirectResponse($fileUrl);
    }
}
