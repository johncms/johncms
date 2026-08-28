<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Settings;

use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\UploadLimits;
use Johncms\Http\View\ViewResponse;
use Johncms\Image\EditorImageFormat;
use Johncms\Image\EditorImageSettings;
use Johncms\Modules\Admin\Application\DTO\EditorImageSettingsDTO;
use Johncms\Modules\Admin\Application\UseCases\UpdateEditorImageSettingsUseCase;
use Johncms\Modules\Admin\Domain\Exceptions\ConfigWriteException;
use Johncms\NavChain;

final readonly class EditorImageSettingsController
{
    private const URL = '/admin/settings/images';

    public function __construct(
        private NavChain $navChain,
        private UpdateEditorImageSettingsUseCase $updateEditorImageSettingsUseCase,
        private EditorImageSettings $settings,
        private UploadLimits $uploadLimits,
        private Session $session,
    ) {
    }

    public function form(): ViewResponse
    {
        return $this->renderForm();
    }

    public function save(Request $request): ViewResponse
    {
        try {
            $this->updateEditorImageSettingsUseCase->execute($this->buildDto($request));
        } catch (ConfigWriteException) {
            return $this->renderForm(__('ERROR: Can not write file `system.local.php`'));
        }

        $this->session->flash('success_message', __('Settings are saved successfully'));
        redirect(self::URL);
    }

    private function buildDto(Request $request): EditorImageSettingsDTO
    {
        return new EditorImageSettingsDTO(
            maxSize: $request->bodyInt('max_size', EditorImageSettings::DEFAULT_MAX_SIZE_KB),
            maxWidth: $request->bodyInt('max_width', EditorImageSettings::DEFAULT_MAX_WIDTH),
            maxHeight: $request->bodyInt('max_height', EditorImageSettings::DEFAULT_MAX_HEIGHT),
            quality: $request->bodyInt('quality', EditorImageSettings::DEFAULT_QUALITY),
            convert: $request->body('convert', EditorImageFormat::Original->value),
        );
    }

    private function renderForm(string $errorMessage = ''): ViewResponse
    {
        $title = __('Images in the editor');
        $this->navChain->add($title);

        $serverLimit = $this->uploadLimits->maxUploadBytes();

        return new ViewResponse(
            '@admin/editor-images.twig',
            [
                'title'           => $title,
                'page_title'      => $title,
                'sys_menu'        => ['editor_images' => true],
                'settings'        => $this->settings(),
                'format_options'  => $this->formatOptions(),
                'server_limit'    => $serverLimit,
                // Said out loud on the page: a limit above what PHP accepts is never reached,
                // and the upload fails before the CMS sees it.
                'exceeds_server'  => $serverLimit > 0 && $this->settings->maxSizeBytes() > $serverLimit,
                'form_action'     => self::URL,
                'error_message'   => $errorMessage,
                'success_message' => (string) $this->session->getFlash('success_message'),
            ]
        );
    }

    /**
     * @return array<string, int|string>
     */
    private function settings(): array
    {
        return [
            'max_size'   => $this->settings->maxSizeKb(),
            'max_width'  => $this->settings->maxWidth() ?? 0,
            'max_height' => $this->settings->maxHeight() ?? 0,
            'quality'    => $this->settings->quality(),
            'convert'    => $this->settings->format()->value,
        ];
    }

    /**
     * @return list<array{value: string, label: string, hint: string}>
     */
    private function formatOptions(): array
    {
        return [
            [
                'value' => EditorImageFormat::Original->value,
                'label' => __('Keep the original format'),
                'hint'  => __('A picture within the bounds is stored exactly as it was uploaded.'),
            ],
            [
                'value' => EditorImageFormat::Webp->value,
                'label' => 'WebP',
                'hint'  => __('The lightest of the three. Every upload is re-encoded.'),
            ],
            [
                'value' => EditorImageFormat::Jpeg->value,
                'label' => 'JPEG',
                'hint'  => __('Every upload is re-encoded. A transparent background turns into a solid one.'),
            ],
        ];
    }
}
