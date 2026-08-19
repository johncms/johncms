<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Auth\CurrentUser;
use Johncms\Content\ContentContext;
use Johncms\Content\ContentRendererInterface;
use Johncms\Modules\Forum\Application\Services\ForumPermissions;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Application\ForumUtils;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\Services\ForumSectionPathService;
use Johncms\Modules\Forum\Application\UseCases\AttachUploadedFilesToMessageUseCase;
use Johncms\Modules\Forum\Application\UseCases\CreateTopicUseCase;
use Johncms\Modules\Forum\Application\UseCases\GetNewTopicContextUseCase;
use Johncms\Modules\Forum\Domain\Models\ForumMessage;
use Johncms\Modules\Forum\Domain\Models\ForumTopic;
use Johncms\Http\Environment;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\NavChain;
use Johncms\Security\AntifloodCheckerInterface;
use Johncms\System\Utility\EditorContentNormalizer;
use Johncms\Validator\Rules\ModelNotExists;
use Johncms\Validator\Rules\StringLength;
use Johncms\Validator\ValidatorInterface;
use Twig\Markup;

final readonly class NewTopicController
{
    public function __construct(
        private Environment $environment,
        private AntifloodCheckerInterface $antifloodChecker,
        private EditorContentNormalizer $editorContentNormalizer,
        private ContentRendererInterface $content,
        private NavChain $navChain,
        private CurrentUser $currentUser,
        private ForumErrorRenderer $forumErrorRenderer,
        private GetNewTopicContextUseCase $contextUseCase,
        private CreateTopicUseCase $createTopicUseCase,
        private AttachUploadedFilesToMessageUseCase $attachUploadedFilesUseCase,
        private ForumSectionPathService $sectionPathService,
        private ValidatorInterface $validator,
        private AccessCheckerInterface $accessChecker,
    ) {
    }

    public function __invoke(Request $request, int $id): ViewResponse
    {
        $page = max(1, $request->queryInt('page', 1));

        try {
            $section = $this->contextUseCase->execute($id);
        } catch (ForumAccessDeniedException $exception) {
            return $this->forumErrorRenderer->viewResponse(
                $exception,
                [
                    'back_url'      => $this->sectionPathService->getSectionUrlById($id) ?? '/forum/',
                    'back_url_name' => __('Go to Section'),
                ]
            );
        } catch (ForumNotFoundException) {
            pageNotFound();
        }

        $flood = $this->antifloodChecker->getRemainingSeconds();
        if ($flood) {
            return new ViewResponse(
                '@theme/pages/result.twig',
                [
                    'title'         => __('New Topic'),
                    'type'          => 'alert-danger',
                    'message'       => new Markup(
                        sprintf(__('You cannot add the message so often<br>Please, wait %d sec.'), $flood),
                        'UTF-8'
                    ),
                    'back_url'      => $section->url . ($page > 1 ? '?page=' . $page : ''),
                    'back_url_name' => __('Back'),
                ]
            );
        }

        $data = [
            'name'       => $request->body('th', ''),
            'message'    => $this->editorContentNormalizer->trimEdgeEmptyBlocks(
                ForumUtils::topicLink($request->body('msg', ''), $request->server->getString('HTTP_HOST', ''))
            ),
            'add_files'  => $request->bodyInt('addfiles', 0),
            'attached_files' => (array) $request->bodyInts('attached_files'),
        ];

        if ($this->accessChecker->allows(ForumPermissions::TOPIC_META_MANAGE)) {
            $data['meta_keywords'] = $request->body('meta_keywords');
            $data['meta_description'] = $request->body('meta_description');
        }

        $errors = [];
        if ($request->body('submit')) {
            $rules = [
                'name'    => [
                    new StringLength(min: 3, max: 200),
                    new ModelNotExists(
                        model: ForumTopic::class,
                        field: 'name',
                        // A topic of this name may exist elsewhere, just not in this section.
                        exclude: static function ($query) use ($id): void {
                            $query->where('section_id', $id);
                        },
                    ),
                ],
                'message' => [
                    new StringLength(min: 4),
                    new ModelNotExists(
                        model: ForumMessage::class,
                        field: 'text',
                        // The same text from the same author is a double post; from somebody
                        // else it is a coincidence.
                        exclude: function ($query): void {
                            $query->where('user_id', $this->currentUser->id());
                        },
                    ),
                ],
            ];

            $validationResult = $this->validator->validate($data, $rules);
            if ($validationResult->isValid()) {
                $result = $this->createTopicUseCase->execute(
                    section: $section,
                    topicName: (string) $data['name'],
                    messageText: (string) $data['message'],
                    metaKeywords: $data['meta_keywords'] ?? null,
                    metaDescription: $data['meta_description'] ?? null,
                    clientInfo: $this->environment->getClientInfo(),
                );
                $this->attachUploadedFilesUseCase->execute(
                    messageId: $result->messageId,
                    attachedFileIds: $data['attached_files'],
                );

                if ($data['add_files'] === 1) {
                    redirect('/forum/addfile/' . $result->messageId . '/');
                }

                redirect($result->topicUrl);
            }

            $errors = $validationResult->getErrors();
        }

        $msgPreview = $this->content->render(
            (string) $data['message'],
            new ContentContext(adminSmilies: $this->accessChecker->allows(CorePermissions::SMILIES_ADMIN_USE))
        );

        ForumUtils::buildBreadcrumbs($section->parent, $section->name, $section->url);
        $this->navChain->add(__('New Topic'));

        return new ViewResponse(
            '@forum/public/new-topic.twig',
            [
                'title'           => __('New Topic'),
                'page_title'      => __('New Topic'),
                'action_url'      => '/forum/new-topic/' . $section->id . '/',
                'th'              => $data['name'],
                'add_files'       => ($data['add_files'] === 1),
                'msg'             => (string) $data['message'],
                'back_url'        => $section->url,
                'show_preview'    => ! empty($data['name']) && ! empty($data['message']) && ! $request->body('submit'),
                'preview_message' => $msgPreview,
                'preview_time'    => time(),
                'can_set_meta'    => $this->accessChecker->allows(ForumPermissions::TOPIC_META_MANAGE),
                'preview_enabled' => ! empty($this->getForumSettings()['preview']),
                'errors'          => $errors,
                'data'            => $data,
            ]
        );
    }

    private function getForumSettings(): array
    {
        $setForumDefault = [
            'farea'    => 0,
            'upfp'     => 0,
            'preview'  => 1,
            'postclip' => 1,
            'postcut'  => 2,
        ];

        $setForum = [];
        if ($this->currentUser->isValid() && ! empty($this->currentUser->user()->set_forum)) {
            $setForum = (array) $this->currentUser->user()->set_forum;
        }

        return array_merge($setForumDefault, $setForum);
    }
}
