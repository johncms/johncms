<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
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
use Johncms\NavChain;
use Johncms\Security\AntifloodCheckerInterface;
use Johncms\Smilies\SmiliesRendererInterface;
use Johncms\Http\Environment;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\System\Utility\EditorContentNormalizer;
use Johncms\Users\User;
use Johncms\Validator\Validator;
use Simba77\EmbedMedia\Embed;
use Twig\Markup;

final readonly class NewTopicController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Environment $environment,
        private AntifloodCheckerInterface $antifloodChecker,
        private SmiliesRendererInterface $smiliesRenderer,
        private EditorContentNormalizer $editorContentNormalizer,
        private \HTMLPurifier $purifier,
        private Embed $embed,
        private NavChain $navChain,
        private User $currentUser,
        private ForumErrorRenderer $forumErrorRenderer,
        private GetNewTopicContextUseCase $contextUseCase,
        private CreateTopicUseCase $createTopicUseCase,
        private AttachUploadedFilesToMessageUseCase $attachUploadedFilesUseCase,
        private ForumSectionPathService $sectionPathService,
    ) {
        $this->controllerContext->initModule('forum');
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
            'csrf_token' => $request->body('csrf_token', ''),
            'add_files'  => $request->bodyInt('addfiles', 0),
            'attached_files' => (array) $request->bodyInts('attached_files'),
        ];

        if ($this->currentUser->rights > 0) {
            $data['meta_keywords'] = $request->body('meta_keywords');
            $data['meta_description'] = $request->body('meta_description');
        }

        $errors = [];
        if ($request->body('submit')) {
            $rules = [
                'name'       => [
                    'NotEmpty',
                    'StringLength'   => ['min' => 3, 'max' => 200],
                    'ModelNotExists' => [
                        'model'   => ForumTopic::class,
                        'field'   => 'name',
                        'exclude' => static function ($query) use ($id) {
                            $query->where('section_id', $id);
                        },
                    ],
                ],
                'message'    => [
                    'NotEmpty',
                    'StringLength'   => ['min' => 4],
                    'ModelNotExists' => [
                        'model'   => ForumMessage::class,
                        'field'   => 'text',
                        'exclude' => function ($query) {
                            $query->where('user_id', $this->currentUser->id);
                        },
                    ],
                ],
                'csrf_token' => ['Csrf'],
            ];

            $validator = new Validator($data, $rules);
            if ($validator->isValid()) {
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

            $errors = $validator->getErrors();
        }

        $msgPreview = $this->purifier->purify((string) $data['message']);
        $msgPreview = $this->embed->embedMedia($msgPreview);
        $msgPreview = $this->smiliesRenderer->render($msgPreview, $this->currentUser->rights > 0);

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
                'preview_message' => new Markup($msgPreview, 'UTF-8'),
                'preview_time'    => time(),
                'can_set_meta'    => $this->currentUser->rights > 0,
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
        if ($this->currentUser->isValid() && ! empty($this->currentUser->set_forum)) {
            $setForum = (array) $this->currentUser->set_forum;
        }

        return array_merge($setForumDefault, $setForum);
    }
}
