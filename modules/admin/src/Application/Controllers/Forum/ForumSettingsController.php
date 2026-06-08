<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Forum;

use Johncms\Http\Controller\AdminControllerContext;
use Johncms\Modules\Admin\Application\DTO\ForumSettingsDTO;
use Johncms\Modules\Admin\Application\UseCases\UpdateForumSettingsUseCase;
use Johncms\Modules\Admin\Domain\Exceptions\ConfigWriteException;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;
use Johncms\Validator\Validator;

final readonly class ForumSettingsController
{
    private const URL = '/admin/forum/settings';

    public function __construct(
        private AdminControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private UpdateForumSettingsUseCase $updateForumSettings,
    ) {
        $this->controllerContext->initModule('admin');
    }

    public function form(): string
    {
        return $this->renderForm();
    }

    public function save(): string
    {
        if (! $this->isCsrfValid()) {
            return $this->renderForm(__('Wrong data'));
        }

        $dto = new ForumSettingsDTO(
            fileCounters: $this->request->getPost('file_counters') !== null,
            topicKeywords: trim((string) $this->request->getPost('topic_keywords', '')),
            topicDescription: trim((string) $this->request->getPost('topic_description', '')),
            sectionKeywords: trim((string) $this->request->getPost('section_keywords', '')),
            sectionDescription: trim((string) $this->request->getPost('section_description', '')),
            forumKeywords: trim((string) $this->request->getPost('forum_keywords', '')),
            forumDescription: trim((string) $this->request->getPost('forum_description', '')),
        );

        try {
            $this->updateForumSettings->execute($dto);
        } catch (ConfigWriteException) {
            return $this->renderForm(__('ERROR: Can not write file `forum.local.php`'));
        }

        $_SESSION['success_message'] = __('Settings are saved successfully');
        redirect(self::URL);
    }

    private function isCsrfValid(): bool
    {
        $validator = new Validator(
            ['csrf_token' => (string) $this->request->getPost('csrf_token', '')],
            ['csrf_token' => ['Csrf']]
        );

        return $validator->isValid();
    }

    private function renderForm(?string $errorMessage = null): string
    {
        $title = __('Forum Settings');
        $this->navChain->add(__('Forum Management'), '/admin/forum');
        $this->navChain->add($title);

        $successMessage = null;
        if (! empty($_SESSION['success_message'])) {
            $successMessage = (string) $_SESSION['success_message'];
            unset($_SESSION['success_message']);
        }

        $this->render->addData([
            'title'       => $title,
            'page_title'  => $title,
            'module_menu' => ['forum' => true],
        ]);

        return $this->render->render('admin::forum/settings', [
            'forum_config'    => config('forum')['settings'],
            'form_action'     => self::URL,
            'error_message'   => $errorMessage,
            'success_message' => $successMessage,
        ]);
    }
}
