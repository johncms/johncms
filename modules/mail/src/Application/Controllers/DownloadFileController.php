<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Mail\Application\Exceptions\MessageNotFoundException;
use Johncms\Modules\Mail\Application\UseCases\DownloadFileUseCase;
use Johncms\System\View\Render;

final readonly class DownloadFileController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private DownloadFileUseCase $downloadFileUseCase,
    ) {
        $this->controllerContext->initModule('mail');
    }

    public function __invoke(int $id): string
    {
        try {
            $fileUrl = $this->downloadFileUseCase->execute($id);
        } catch (MessageNotFoundException) {
            return $this->render->render(
                'system::pages/result',
                [
                    'title'   => __('Mail'),
                    'type'    => 'alert-danger',
                    'message' => __('Such file does not exist'),
                    'back_url' => '/mail/files',
                ]
            );
        }

        header('Location: ' . $fileUrl);
        exit;
    }
}
