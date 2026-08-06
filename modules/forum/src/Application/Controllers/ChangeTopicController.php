<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\UseCases\ChangeTopicUseCase;
use Johncms\Modules\Forum\Application\UseCases\GetChangeTopicContextUseCase;
use Johncms\Modules\Forum\Domain\Models\ForumTopic;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\Validator\Validator;

final readonly class ChangeTopicController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private ForumErrorRenderer $forumErrorRenderer,
        private GetChangeTopicContextUseCase $contextUseCase,
        private ChangeTopicUseCase $changeTopicUseCase,
    ) {
        $this->controllerContext->initModule('forum');
    }

    public function __invoke(Request $request, int $id): ViewResponse
    {
        try {
            $topic = $this->contextUseCase->execute($id);
        } catch (ForumAccessDeniedException | ForumNotFoundException $exception) {
            return $this->forumErrorRenderer->viewResponse(
                $exception,
                [
                    'title'         => __('Change the topic'),
                    'page_title'    => __('Change the topic'),
                    'message'       => __('Wrong data'),
                    'back_url'      => '/forum/',
                    'back_url_name' => __('Back'),
                ]
            );
        }

        $formData = [
            'name'             => $request->body('name', (string) $topic->name),
            'meta_keywords'    => $request->body('meta_keywords', $topic->meta_keywords ?? ''),
            'meta_description' => $request->body('meta_description', $topic->meta_description ?? ''),
            'csrf_token'       => $request->body('csrf_token', ''),
        ];

        $errors = [];
        if ($request->getMethod() === 'POST') {
            $rules = [
                'name'          => [
                    'NotEmpty',
                    'StringLength'   => ['min' => 3, 'max' => 200],
                    'ModelNotExists' => [
                        'model'   => ForumTopic::class,
                        'field'   => 'name',
                        'exclude' => static function ($query) use ($topic, $id) {
                            $query->where('section_id', $topic->section_id)
                                ->where('id', '!=', $id);
                        },
                    ],
                ],
                'meta_keywords' => [
                    'StringLength' => ['max' => 250],
                ],
                'meta_description' => [
                    'StringLength' => ['max' => 65000],
                ],
                'csrf_token'    => ['Csrf'],
            ];

            $validator = new Validator($formData, $rules);
            if ($validator->isValid()) {
                $this->changeTopicUseCase->execute(
                    topic:           $topic,
                    name:            $formData['name'],
                    metaKeywords:    $formData['meta_keywords'],
                    metaDescription: $formData['meta_description'],
                );

                redirect($topic->url);
            }

            $errors = $validator->getErrors();
        }

        return new ViewResponse(
            '@forum/public/change-topic.twig',
            [
                'title'      => __('Change the topic'),
                'page_title' => __('Change the topic'),
                'action_url' => '/forum/change-topic/' . $topic->id . '/',
                'form_data'  => $formData,
                'back_url'   => $topic->url,
                'errors'     => $errors,
            ]
        );
    }
}
