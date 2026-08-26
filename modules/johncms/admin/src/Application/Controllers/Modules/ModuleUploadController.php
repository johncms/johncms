<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Modules;

use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\ModuleInstallService;
use Johncms\Modules\ModuleRegistry;
use Johncms\Modules\ModuleStatus;
use Johncms\Modules\Package\ModulePackageException;
use Johncms\Modules\Package\ModulePackageInstaller;
use Johncms\NavChain;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Throwable;

/**
 * Installing a module from an archive somebody uploads.
 *
 * This is the screen through which arbitrary code reaches the site, so the archive is inspected
 * before a byte of it is written anywhere (ModulePackageValidator), the operation is logged, and
 * whoever opens the screen needs system.modules.manage — which no built-in role carries.
 */
final readonly class ModuleUploadController
{
    private const string URL = '/admin/modules';

    public function __construct(
        private NavChain $navChain,
        private ModulePackageInstaller $packages,
        private ModuleInstallService $modules,
        private ModuleRegistry $registry,
        private Session $session,
        private LoggerInterface $logger,
    ) {
    }

    public function form(): ViewResponse
    {
        $title = __('Install a module from a file');
        $this->navChain->add(__('Modules'), self::URL);
        $this->navChain->add($title);

        return new ViewResponse('@admin/modules-upload.twig', [
            'title'         => $title,
            'page_title'    => $title,
            'module_menu'   => ['modules' => true],
            'action_url'    => self::URL . '/upload',
            'back_url'      => self::URL,
            'error_message' => (string) $this->session->getFlash('error_message'),
        ]);
    }

    public function upload(Request $request): ViewResponse
    {
        $file = $request->files->get('package');

        if (! $file instanceof UploadedFile || ! $file->isValid()) {
            return $this->refuse(__('Choose a zip file with the module'));
        }

        $temporary = DATA_PATH . 'tmp' . DIRECTORY_SEPARATOR . 'module-upload-' . bin2hex(random_bytes(8)) . '.zip';

        if (! is_dir(dirname($temporary))) {
            mkdir(dirname($temporary), 0o755, true);
        }

        try {
            $file->move(dirname($temporary), basename($temporary));

            $key = $this->packages->extractKeyOnly($temporary);
            $known = $this->registry->find($key);
            $wasInstalled = $known !== null && $known->status !== ModuleStatus::Discovered;

            $this->packages->extract($temporary);
            $this->registry->forget();

            // An archive of a module the site already has is an update: the files have just been
            // replaced, and what is left is the migrations and the hooks of the module.
            $result = $wasInstalled ? $this->modules->update($key) : $this->modules->install($key);

            $this->logger->info(
                sprintf('Module uploaded: %s', $result->isSuccessful() ? 'done' : 'failed'),
                ['module' => $key, 'steps' => $result->steps()]
            );

            $this->session->flash('operation_steps', $result->steps());
            $result->isSuccessful()
                ? $this->session->flash('success_message', __('Done'))
                : $this->session->flash('error_message', (string) $result->error());
        } catch (ModulePackageException $exception) {
            return $this->refuse($exception->getMessage());
        } catch (Throwable $exception) {
            $this->logger->error('Module upload failed.', ['exception' => $exception]);

            return $this->refuse(__('An error occurred'));
        } finally {
            if (is_file($temporary)) {
                unlink($temporary);
            }
        }

        redirect(self::URL);
    }

    private function refuse(string $message): ViewResponse
    {
        $this->session->flash('error_message', $message);

        redirect(self::URL . '/upload');
    }
}
