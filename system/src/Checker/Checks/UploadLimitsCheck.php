<?php

declare(strict_types=1);

namespace Johncms\Checker\Checks;

use Johncms\Checker\CheckSeverity;
use Johncms\Checker\CheckStatus;
use Johncms\Checker\DTO\CheckDefinitionDTO;
use Johncms\Checker\DTO\CheckResultDTO;
use Johncms\Checker\PhpEnvironmentInterface;
use Johncms\Checker\SystemCheckInterface;
use Johncms\Http\UploadLimits;
use Johncms\Image\EditorImageSettings;

final readonly class UploadLimitsCheck implements SystemCheckInterface
{
    public function __construct(private PhpEnvironmentInterface $php, private UploadLimits $limits, private EditorImageSettings $images)
    {
    }

    public static function definition(): CheckDefinitionDTO
    {
        return new CheckDefinitionDTO('uploads.limits', d__('system', 'Upload limits'), d__('system', 'Uploads'), 'package', order: 50);
    }

    public function run(): array
    {
        $enabled = filter_var($this->php->ini('file_uploads'), FILTER_VALIDATE_BOOL);
        $upload = $this->limits->uploadMaxFilesizeBytes();
        $post = $this->limits->postMaxSizeBytes();
        $consistent = $post === 0 || ($upload > 0 && $post > $upload);
        $configured = $this->images->maxSizeBytes();
        $fits = ($upload === 0 || $configured <= $upload) && ($post === 0 || $configured < $post);

        return [
            new CheckResultDTO(
                'uploads.enabled',
                'file_uploads',
                $enabled ? d__('system', 'Yes') : d__('system', 'No'),
                $enabled ? CheckStatus::Passed : CheckStatus::Failed,
                CheckSeverity::Critical,
                d__('system', 'Enable file_uploads in PHP to accept uploaded files.'),
            ),
            new CheckResultDTO(
                'uploads.php_limits',
                'upload_max_filesize / post_max_size',
                $this->php->ini('upload_max_filesize') . ' / ' . $this->php->ini('post_max_size'),
                ! $enabled ? CheckStatus::Skipped : ($consistent ? CheckStatus::Passed : CheckStatus::Failed),
                CheckSeverity::Warning,
                d__('system', 'post_max_size must allow the uploaded file and the rest of the form. Set it higher than upload_max_filesize, or use 0 for no POST size limit.'),
                'https://www.php.net/manual/en/ini.core.php#ini.post-max-size',
            ),
            new CheckResultDTO(
                'uploads.editor_limit',
                d__('system', 'Editor image upload limit'),
                sprintf('%.1f MiB', $configured / 1048576),
                ! $enabled ? CheckStatus::Skipped : ($fits ? CheckStatus::Passed : CheckStatus::Failed),
                CheckSeverity::Warning,
                d__('system', 'The image size allowed in editor settings must fit within the PHP upload limit, with room for the form in post_max_size.'),
            ),
        ];
    }
}
