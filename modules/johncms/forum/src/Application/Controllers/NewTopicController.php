<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Database\TransactionManagerInterface;
use Johncms\EditorUploads\EditorUploadBindException;
use Johncms\Modules\Forum\Application\Uploads\ForumMessagePictures;
use Johncms\Modules\Forum\Application\Uploads\ForumPostFiles;
use Johncms\Storage\StorageException;
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
use Johncms\Modules\Forum\Application\Services\ForumFormTokens;
use Johncms\Modules\Forum\Application\Services\ForumSectionPathService;
use Johncms\Modules\Forum\Application\UseCases\CreateTopicUseCase;
use Johncms\Modules\Forum\Application\UseCases\GetNewTopicContextUseCase;
use Johncms\Modules\Forum\Domain\Models\ForumMessage;
use Johncms\Modules\Forum\Domain\Models\ForumTopic;
use Johncms\Http\Environment;
use Johncms\Http\Request;
use Johncms\Http\UploadedFileMapper;
use Johncms\Http\View\ViewResponse;
use Symfony\Component\HttpFoundation\Response;
use Johncms\NavChain;
use Johncms\Security\AntifloodCheckerInterface;
use Johncms\System\Utility\EditorContentNormalizer;
use Johncms\Validator\Rules\ModelNotExists;
use Johncms\Validator\Rules\StringLength;
use Johncms\Validator\ValidatorInterface;

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
        private ForumMessagePictures $pictures,
        private TransactionManagerInterface $transactions,
        private ForumSectionPathService $sectionPathService,
        private ValidatorInterface $validator,
        private AccessCheckerInterface $accessChecker,
        private ForumFormTokens $formTokens,
        private ForumPostFiles $postFiles,
        private UploadedFileMapper $uploadedFiles,
    ) {
    }

    public function __invoke(Request $request, int $id): ViewResponse
    {
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

        $data = [
            'name'       => $request->body('th', ''),
            'message'    => $this->editorContentNormalizer->trimEdgeEmptyBlocks(
                ForumUtils::topicLink($request->body('msg', ''), $request->server->getString('HTTP_HOST', ''))
            ),
        ];

        if ($this->accessChecker->allows(ForumPermissions::TOPIC_META_MANAGE)) {
            $data['meta_keywords'] = $request->body('meta_keywords');
            $data['meta_description'] = $request->body('meta_description');
        }

        $files = $this->uploadedFiles->fromField($request->files->get('files'));
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
            foreach ($this->postFiles->check($files) as $fileError) {
                $validationResult = $validationResult->withError('files', $fileError);
            }

            $flood = $this->antifloodChecker->getRemainingSeconds();
            if ($flood > 0) {
                $validationResult = $validationResult->withError('_form', sprintf(d__('system', 'You cannot write so often. Wait %d seconds.'), $flood));
            }

            if (! $this->formTokens->consume($request->body('token', ''))) {
                $validationResult = $validationResult->withError('_form', __('The form was already sent or is out of date. Send it again if needed.'));
            }

            $errors = $validationResult->getErrors();
            if ($validationResult->isValid()) {
                try {
                    $result = $this->transactions->run(function () use ($section, $data, $files) {
                        $result = $this->createTopicUseCase->execute(
                            section: $section,
                            topicName: (string) $data['name'],
                            messageText: (string) $data['message'],
                            metaKeywords: $data['meta_keywords'] ?? null,
                            metaDescription: $data['meta_description'] ?? null,
                            clientInfo: $this->environment->getClientInfo(),
                        );
                        $this->pictures->bind($result->messageId, $this->currentUser->id());
                        // Last: a failure after the files are written would leave them behind.
                        $this->postFiles->store($result->messageId, $files);

                        return $result;
                    });

                    redirect($result->topicUrl);
                } catch (EditorUploadBindException $exception) {
                    $errors = ['message' => [$exception->getMessage()]];
                } catch (StorageException) {
                    $errors = ['files' => [__('The files could not be saved. Try again.')]];
                }
            }
        }

        $msgPreview = $this->content->render(
            (string) $data['message'],
            new ContentContext(adminSmilies: $this->accessChecker->allows(CorePermissions::SMILIES_ADMIN_USE))
        );

        $this->navChain->add(__('Forum'), '/forum/');
        ForumUtils::buildBreadcrumbs($section->parent, $section->name, $section->url);
        $this->navChain->add(__('New Topic'));

        return new ViewResponse(
            '@forum/public/new-topic.twig',
            [
                'title'           => __('New Topic'),
                'page_title'      => __('New Topic'),
                'action_url'      => '/forum/new-topic/' . $section->id . '/',
                'th'              => $data['name'],
                'file_limits'     => $this->postFiles->limits(),
                'msg'             => (string) $data['message'],
                'back_url'        => $section->url,
                'section_name'    => $section->name,
                'show_preview'    => ! empty($data['name']) && ! empty($data['message']) && ! $request->body('submit'),
                'preview_message' => $msgPreview,
                'preview_time'    => time(),
                'can_set_meta'    => $this->accessChecker->allows(ForumPermissions::TOPIC_META_MANAGE),
                'errors'          => $errors,
                'data'            => $data,
                'token'           => $this->formTokens->issue(),
            ],
            $errors ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK,
        );
    }
}
