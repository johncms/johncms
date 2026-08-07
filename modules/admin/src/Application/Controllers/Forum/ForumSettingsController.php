<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Forum;

use Johncms\Http\Controller\AdminControllerContext;
use Johncms\Modules\Admin\Application\DTO\ForumSettingsDTO;
use Johncms\Modules\Admin\Application\UseCases\UpdateForumSettingsUseCase;
use Johncms\Modules\Admin\Domain\Exceptions\ConfigWriteException;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\View\ViewResponse;
use Johncms\Validator\Validator;

final readonly class ForumSettingsController
{
    private const URL = '/admin/forum/settings';

    public function __construct(
        private AdminControllerContext $controllerContext,
        private NavChain $navChain,
        private UpdateForumSettingsUseCase $updateForumSettings,
        private Session $session,
    ) {
        $this->controllerContext->initModule('admin');
    }

    public function form(): ViewResponse
    {
        return $this->renderForm();
    }

    public function save(Request $request): ViewResponse
    {
        if (! $this->isCsrfValid($request)) {
            return $this->renderForm(__('Wrong data'));
        }

        $dto = new ForumSettingsDTO(
            fileCounters: $request->hasBody('file_counters'),
            topicKeywords: trim($request->body('topic_keywords', '')),
            topicDescription: trim($request->body('topic_description', '')),
            sectionKeywords: trim($request->body('section_keywords', '')),
            sectionDescription: trim($request->body('section_description', '')),
            forumKeywords: trim($request->body('forum_keywords', '')),
            forumDescription: trim($request->body('forum_description', '')),
        );

        try {
            $this->updateForumSettings->execute($dto);
        } catch (ConfigWriteException) {
            return $this->renderForm(__('ERROR: Can not write file `forum.local.php`'));
        }

        $this->session->flash('success_message', __('Settings are saved successfully'));
        redirect(self::URL);
    }

    private function isCsrfValid(Request $request): bool
    {
        $validator = new Validator(
            ['csrf_token' => $request->body('csrf_token', '')],
            ['csrf_token' => ['Csrf']]
        );

        return $validator->isValid();
    }

    private function renderForm(string $errorMessage = ''): ViewResponse
    {
        $title = __('Forum Settings');
        $this->navChain->add(__('Forum Management'), '/admin/forum');
        $this->navChain->add($title);

        return new ViewResponse('@admin/forum-settings.twig', [
            'title'           => $title,
            'page_title'      => $title,
            'module_menu'     => ['forum' => true],
            'settings'        => (array) config('forum.settings', []),
            'form_action'     => self::URL,
            'error_message'   => $errorMessage,
            'success_message' => (string) $this->session->getFlash('success_message'),
        ]);
    }
}
